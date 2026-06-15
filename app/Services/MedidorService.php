<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FacturaAguaPeriodo;
use App\Models\LecturaGrupoPeriodo;
use App\Models\LecturaMedidor;
use App\Models\MedidorGrupo;
use App\Models\PeriodoExpensa;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MedidorService
{
    /**
     * Registrar lectura de medidor individual
     *
     * @param int $periodoId ID del período
     * @param int $unidadId ID de la unidad
     * @param float $lecturaActual Lectura actual del medidor
     * @param string|null $observaciones Observaciones opcionales
     * @return LecturaMedidor
     * @throws \Exception
     */
    public function registrarLecturaIndividual(
        int $periodoId,
        int $unidadId,
        float $lecturaActual,
        ?string $observaciones = null
    ): LecturaMedidor {
        return DB::transaction(function () use ($periodoId, $unidadId, $lecturaActual, $observaciones) {
            // Verificar que el período exista y esté abierto
            $periodo = PeriodoExpensa::findOrFail($periodoId);
            if ($periodo->estado !== 'abierto') {
                throw new \Exception("El período {$periodo->nombre_periodo} está cerrado. No se pueden registrar lecturas.");
            }

            // Verificar que la unidad exista y tenga medidor
            $unidad = Unidad::where('id', $unidadId)
                ->where('tiene_medidor', true)
                ->where('activo', true)
                ->firstOrFail();

            // Verificar que ya no existe lectura para este período y unidad
            $lecturaExistente = LecturaMedidor::where('periodo_id', $periodoId)
                ->where('unidad_id', $unidadId)
                ->first();

            if ($lecturaExistente) {
                throw new \Exception("Ya existe una lectura registrada para {$unidad->nombre_completo} en este período.");
            }

            // Obtener lectura anterior
            $lecturaAnterior = $this->obtenerLecturaAnterior($unidadId, $periodoId);
            $valorLecturaAnterior = $lecturaAnterior?->lectura_actual ?: 0.0;

            // Validar que la lectura actual sea mayor o igual a la anterior
            $this->validarLecturaActualMayorAnterior($lecturaActual, $valorLecturaAnterior);

            // Calcular consumo
            $consumoM3 = $lecturaActual - $valorLecturaAnterior;

            // Crear lectura
            $lectura = LecturaMedidor::create([
                'periodo_id' => $periodoId,
                'unidad_id' => $unidadId,
                'lectura_anterior' => $valorLecturaAnterior,
                'lectura_actual' => $lecturaActual,
                'consumo_m3' => $consumoM3,
                'observaciones' => $observaciones,
                'registrado_por' => auth()->id(),
            ]);

            // Registrar en activity log
            $this->registrarActivityLog(
                'lectura.registrada',
                "Lectura individual registrada: {$unidad->nombre_completo} - Consumo: {$consumoM3} M³",
                $lectura,
                [
                    'unidad' => $unidad->nombre_completo,
                    'lectura_anterior' => $valorLecturaAnterior,
                    'lectura_actual' => $lecturaActual,
                    'consumo_m3' => $consumoM3,
                ]
            );

            Log::info('Lectura individual registrada', [
                'lectura_id' => $lectura->id,
                'periodo_id' => $periodoId,
                'unidad_id' => $unidadId,
                'consumo_m3' => $consumoM3,
                'usuario' => auth()->user()?->id,
            ]);

            return $lectura;
        });
    }

    /**
     * Registrar lectura de medidor compartido
     *
     * @param int $periodoId ID del período
     * @param int $grupoId ID del grupo de medidores
     * @param float $lecturaActual Lectura actual del medidor compartido
     * @param string|null $observaciones Observaciones opcionales
     * @return array Array de lecturas creadas
     * @throws \Exception
     */
    public function registrarLecturaCompartida(
        int $periodoId,
        int $grupoId,
        float $lecturaActual,
        ?string $observaciones = null
    ): array {
        return DB::transaction(function () use ($periodoId, $grupoId, $lecturaActual, $observaciones) {
            // Verificar que el período exista y esté abierto
            $periodo = PeriodoExpensa::findOrFail($periodoId);
            if ($periodo->estado !== 'abierto') {
                throw new \Exception("El período {$periodo->nombre_periodo} está cerrado. No se pueden registrar lecturas.");
            }

            // Verificar que ya no existe lectura de grupo para este período
            $lecturaGrupoExistente = LecturaGrupoPeriodo::where('periodo_id', $periodoId)
                ->where('medidor_grupo_id', $grupoId)
                ->first();

            if ($lecturaGrupoExistente) {
                throw new \Exception("Ya existe una lectura compartida registrada para este grupo en este período.");
            }

            // Obtener grupo y sus unidades activas
            $grupo = MedidorGrupo::with('unidades')->findOrFail($grupoId);

            if (!$grupo->activo) {
                throw new \Exception("El grupo de medidores '{$grupo->nombre}' no está activo.");
            }

            $unidades = $grupo->unidades;

            if ($unidades->isEmpty()) {
                throw new \Exception("El grupo '{$grupo->nombre}' no tiene unidades asignadas.");
            }

            // Obtener lectura anterior del grupo (del período anterior)
            $lecturaAnteriorGrupo = $this->obtenerLecturaAnteriorCompartida($grupoId, $periodoId);
            $valorLecturaAnterior = $lecturaAnteriorGrupo?->lectura_actual ?? 0.0;

            // Validar lectura
            $this->validarLecturaActualMayorAnterior($lecturaActual, $valorLecturaAnterior);

            // Calcular consumo total
            $consumoTotalM3 = $lecturaActual - $valorLecturaAnterior;

            // Distribuir equitativamente
            $cantidadUnidades = $unidades->count();
            $consumoPorUnidadM3 = $consumoTotalM3 / $cantidadUnidades;

            // Crear registro en lectura_grupos_periodos
            $lecturaGrupo = LecturaGrupoPeriodo::create([
                'periodo_id' => $periodoId,
                'medidor_grupo_id' => $grupoId,
                'lectura_anterior' => $valorLecturaAnterior,
                'lectura_actual' => $lecturaActual,
                'consumo_total' => $consumoTotalM3,
                'observaciones' => $observaciones,
                'registrado_por' => auth()->id(),
            ]);

            // Crear lecturas distribuidas para cada unidad en lecturas_medidor
            $lecturasCreadas = [];
            foreach ($unidades as $unidad) {
                $lectura = LecturaMedidor::create([
                    'periodo_id' => $periodoId,
                    'unidad_id' => $unidad->id,
                    'medidor_grupo_id' => $grupoId,
                    'lectura_anterior' => null,
                    'lectura_actual' => null,
                    'consumo_m3' => round($consumoPorUnidadM3, 2),
                    'observaciones' => $observaciones,
                    'registrado_por' => auth()->id(),
                ]);
                $lecturasCreadas[] = $lectura;
            }

            // Registrar en activity log
            $this->registrarActivityLog(
                'lectura.compartida_registrada',
                "Lectura compartida registrada: Grupo '{$grupo->nombre}' ({$cantidadUnidades} unidades) - Consumo total: {$consumoTotalM3} M³",
                $lecturaGrupo,
                [
                    'grupo' => $grupo->nombre,
                    'grupo_id' => $grupoId,
                    'cantidad_unidades' => $cantidadUnidades,
                    'lectura_anterior' => $valorLecturaAnterior,
                    'lectura_actual' => $lecturaActual,
                    'consumo_total_m3' => $consumoTotalM3,
                    'consumo_por_unidad_m3' => round($consumoPorUnidadM3, 2),
                ]
            );

            Log::info('Lectura compartida registrada', [
                'lectura_grupo_id' => $lecturaGrupo->id,
                'grupo_id' => $grupoId,
                'periodo_id' => $periodoId,
                'consumo_total_m3' => $consumoTotalM3,
                'cantidad_unidades' => $cantidadUnidades,
                'usuario' => auth()->user()?->id,
            ]);

            return $lecturasCreadas;
        });
    }

    /**
     * Obtener la lectura anterior del período anterior
     *
     * @param int $unidadId ID de la unidad
     * @param int $periodoId ID del período actual
     * @return LecturaMedidor|null
     */
    public function obtenerLecturaAnterior(int $unidadId, int $periodoId): ?LecturaMedidor
    {
        $periodoActual = PeriodoExpensa::findOrFail($periodoId);

        // Buscar períodos anteriores
        $periodoAnterior = PeriodoExpensa::where(function ($query) use ($periodoActual) {
            if ($periodoActual->mes === 1) {
                // Si es enero, buscar diciembre del año anterior
                $query->where('mes', 12)->where('año', $periodoActual->año - 1);
            } else {
                // Buscar mes anterior del mismo año
                $query->where('mes', $periodoActual->mes - 1)->where('año', $periodoActual->año);
            }
        })->first();

        if (!$periodoAnterior) {
            return null;
        }

        // Buscar la última lectura de la unidad en cualquier período anterior
        return LecturaMedidor::where('unidad_id', $unidadId)
            ->whereHas('unidad', fn($q) => $q->where('tipo_medidor', 'individual'))
            ->whereHas('periodo', function ($query) use ($periodoActual) {
                $query->where(function ($q) use ($periodoActual) {
                    $q->where('año', '<', $periodoActual->año)
                        ->orWhere(function ($q2) use ($periodoActual) {
                            $q2->where('año', $periodoActual->año)
                                ->where('mes', '<', $periodoActual->mes);
                        });
                });
            })
            ->orderBy('periodo_id', 'desc')
            ->first();
    }

    /**
     * Obtener la lectura anterior compartida del período anterior
     *
     * @param int $grupoId ID del grupo
     * @param int $periodoId ID del período actual
     * @return LecturaGrupoPeriodo|null
     */
    public function obtenerLecturaAnteriorCompartida(int $grupoId, int $periodoId): ?LecturaGrupoPeriodo
    {
        $periodoActual = PeriodoExpensa::findOrFail($periodoId);

        return LecturaGrupoPeriodo::where('medidor_grupo_id', $grupoId)
            ->whereHas('periodo', function ($query) use ($periodoActual) {
                $query->where(function ($q) use ($periodoActual) {
                    $q->where('año', '<', $periodoActual->año)
                        ->orWhere(function ($q2) use ($periodoActual) {
                            $q2->where('año', $periodoActual->año)
                                ->where('mes', '<', $periodoActual->mes);
                        });
                });
            })
            ->orderBy('periodo_id', 'desc')
            ->first();
    }

    /**
     * Validar que la lectura actual sea mayor o igual a la anterior
     *
     * @param float $lecturaActual Lectura actual
     * @param float $lecturaAnterior Lectura anterior
     * @return bool
     * @throws \Exception
     */
    public function validarLecturaActualMayorAnterior(float $lecturaActual, float $lecturaAnterior): bool
    {
        if ($lecturaActual < $lecturaAnterior) {
            throw new \Exception(
                "La lectura actual ({$lecturaActual} M³) no puede ser menor a la lectura anterior ({$lecturaAnterior} M³)."
            );
        }

        return true;
    }

    /**
     * Calcular el factor de agua domiciliario
     *
     * @param int $periodoId ID del período
     * @return float Factor de agua Bs/M³
     * @throws \Exception
     */
    public function calcularFactorDomiciliario(int $periodoId): float
    {
        // Obtener las 2 facturas domiciliarias
        $facturasDomiciliarias = FacturaAguaPeriodo::where('periodo_id', $periodoId)
            ->where('categoria', 'domiciliaria')
            ->get();

        if ($facturasDomiciliarias->count() !== 2) {
            throw new \Exception("Se requieren 2 facturas domiciliarias para calcular el factor. Actual: {$facturasDomiciliarias->count()}");
        }

        $totalImporte = $facturasDomiciliarias->sum('importe');
        $totalConsumoM3 = $facturasDomiciliarias->sum('consumo_m3');

        $factor = $totalConsumoM3 > 0
            ? ($totalImporte / $totalConsumoM3)
            : 0.0;

        Log::info('Factor domiciliario calculado', [
            'periodo_id' => $periodoId,
            'total_importe' => $totalImporte,
            'total_consumo_m3' => $totalConsumoM3,
            'factor' => $factor,
        ]);

        return round($factor, 4);
    }

    /**
     * Calcular el factor de agua comercial
     *
     * @param int $periodoId ID del período
     * @return float Factor de agua Bs/M³
     * @throws \Exception
     */
    public function calcularFactorComercial(int $periodoId): float
    {
        // Obtener la factura comercial
        $facturaComercial = FacturaAguaPeriodo::where('periodo_id', $periodoId)
            ->where('categoria', 'comercial')
            ->first();

        if (!$facturaComercial) {
            throw new \Exception("No se encontró la factura comercial para este período.");
        }

        $factor = $facturaComercial->consumo_m3 > 0
            ? ($facturaComercial->importe / $facturaComercial->consumo_m3)
            : 0.0;

        Log::info('Factor comercial calculado', [
            'periodo_id' => $periodoId,
            'importe' => $facturaComercial->importe,
            'consumo_m3' => $facturaComercial->consumo_m3,
            'factor' => $factor,
        ]);

        return round($factor, 4);
    }

    /**
     * Calcular el monto de agua aplicando redondeo monetario
     *
     * @param float $consumoM3 Consumo en M³
     * @param float $factorAgua Factor de agua Bs/M³
     * @return float Monto redondeado en Bs
     */
    public function calcularMontoAgua(float $consumoM3, float $factorAgua): float
    {
        $monto = $consumoM3 * $factorAgua;

        // Redondeo monetario: < 0.50 ↓, >= 0.50 ↑
        $decimal = $monto - floor($monto);

        if ($decimal < 0.50) {
            return floor($monto);
        } else {
            return ceil($monto);
        }
    }

    /**
     * Obtener el progreso de registro de lecturas
     *
     * @param int $periodoId ID del período
     * @return array
     */
    public function obtenerProgresoLecturas(int $periodoId): array
    {
        // Total de unidades con medidor (NO parqueos/bauleras)
        $totalUnidades = Unidad::where('activo', true)
            ->where('tiene_medidor', true)
            ->count();

        // Total de lecturas registradas en el período
        $totalRegistradas = LecturaMedidor::where('periodo_id', $periodoId)
            ->count();

        // Total pendientes
        $totalPendientes = max(0, $totalUnidades - $totalRegistradas);

        // Porcentaje de progreso
        $porcentaje = $totalUnidades > 0
            ? round(($totalRegistradas / $totalUnidades) * 100, 1)
            : 0;

        return [
            'total_unidades' => $totalUnidades,
            'total_registradas' => $totalRegistradas,
            'total_pendientes' => $totalPendientes,
            'porcentaje' => $porcentaje,
            'completo' => $totalPendientes === 0,
        ];
    }

    /**
     * Obtener unidades que aún no tienen lectura registrada
     *
     * @param int $periodoId ID del período
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerUnidadesSinLectura(int $periodoId)
    {
        $unidadesConLectura = LecturaMedidor::where('periodo_id', $periodoId)
            ->pluck('unidad_id');

        return Unidad::where('activo', true)
            ->where('tiene_medidor', true)
            ->whereNotIn('id', $unidadesConLectura)
            ->with(['propietarioActual', 'medidorGrupo'])
            ->get()
            ->map(function ($unidad) {
                return [
                    'id' => $unidad->id,
                    'nombre_completo' => $unidad->nombre_completo,
                    'codigo' => $unidad->codigo,
                    'tipo' => $unidad->tipo_unidad->getLabel(),
                    'categoria' => $unidad->categoria_unidad->getLabel(),
                    'piso' => $unidad->piso,
                    'propietario' => $unidad->propietarioActual?->nombre . ' ' . $unidad->propietarioActual?->apellido,
                    'tiene_medidor_compartido' => $unidad->tieneMedidorCompartido(),
                    'grupo' => $unidad->medidorGrupo->first()?->nombre,
                ];
            });
    }

    /**
     * Obtener todas las lecturas de un período con información detallada
     *
     * @param int $periodoId ID del período
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerLecturasDelPeriodo(int $periodoId)
    {
        return LecturaMedidor::where('periodo_id', $periodoId)
            ->with(['unidad.propietarioActual', 'medidorGrupo'])
            ->get()
            ->map(function ($lectura) {
                $unidad = $lectura->unidad;
                $propietario = $unidad->propietarioActual;

                return [
                    'id' => $lectura->id,
                    'unidad_id' => $unidad->id,
                    'nombre_unidad' => $unidad->nombre_completo,
                    'codigo' => $unidad->codigo,
                    'tipo' => $lectura->tipo_label,
                    'categoria' => $unidad->categoria_unidad->getLabel(),
                    'propietario' => $propietario?->nombre . ' ' . $propietario?->apellido,
                    'lectura_anterior' => $lectura->lectura_anterior,
                    'lectura_actual' => $lectura->lectura_actual,
                    'consumo_m3' => $lectura->consumo_m3,
                    'grupo' => $lectura->medidorGrupo?->nombre,
                    'observaciones' => $lectura->observaciones,
                ];
            });
    }

    /**
     * Registrar actividad en el log
     *
     * @param string $action Acción realizada
     * @param string $description Descripción
     * @param \Illuminate\Database\Eloquent\Model $model Modelo relacionado
     * @param array $properties Propiedades adicionales
     * @return void
     */
    private function registrarActivityLog(
        string $action,
        string $description,
        \Illuminate\Database\Eloquent\Model $model,
        array $properties = []
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'properties' => array_merge([
                'periodo_id' => $model->periodo_id ?? null,
            ], $properties),
        ]);
    }
}
