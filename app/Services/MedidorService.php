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
     * @param array $distribucionManual [unidad_id => consumo]. Si está vacío, se distribuye equitativamente.
     * @return array Array de lecturas creadas
     * @throws \Exception
     */
    public function registrarLecturaCompartida(
        int $periodoId,
        int $grupoId,
        float $lecturaActual,
        ?string $observaciones = null,
        array $distribucionManual = []
    ): array {
        return DB::transaction(function () use ($periodoId, $grupoId, $lecturaActual, $observaciones, $distribucionManual) {
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
            $grupo = MedidorGrupo::with(['unidades' => function ($q) {
                $q->where('activo', true);
            }])->findOrFail($grupoId);

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

            // Determinar distribución: manual o equitativa
            if (!empty($distribucionManual)) {
                $distribucionFinal = $this->normalizarDistribucion(
                    $distribucionManual,
                    $unidades,
                    $consumoTotalM3
                );
                $cantidadUnidades = $unidades->count();
                $consumoPorUnidadM3 = $cantidadUnidades > 0
                    ? ($consumoTotalM3 / $cantidadUnidades)
                    : 0.0;
            } else {
                $cantidadUnidades = $unidades->count();
                $consumoPorUnidadM3 = $consumoTotalM3 / $cantidadUnidades;
                $distribucionFinal = [];
                foreach ($unidades as $unidad) {
                    $distribucionFinal[$unidad->id] = round($consumoPorUnidadM3, 2);
                }
            }

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
                    'consumo_m3' => $distribucionFinal[$unidad->id] ?? round($consumoPorUnidadM3, 2),
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
                    'distribucion_manual' => !empty($distribucionManual),
                    'distribucion' => $distribucionFinal,
                ]
            );

            Log::info('Lectura compartida registrada', [
                'lectura_grupo_id' => $lecturaGrupo->id,
                'grupo_id' => $grupoId,
                'periodo_id' => $periodoId,
                'consumo_total_m3' => $consumoTotalM3,
                'cantidad_unidades' => $cantidadUnidades,
                'distribucion_manual' => !empty($distribucionManual),
                'usuario' => auth()->user()?->id,
            ]);

            return $lecturasCreadas;
        });
    }

    /**
     * Actualizar una lectura individual existente
     *
     * @param int $lecturaId ID de la lectura a actualizar
     * @param float $lecturaActual Nueva lectura actual
     * @param string|null $observaciones Observaciones opcionales
     * @return LecturaMedidor
     * @throws \Exception
     */
    public function actualizarLecturaIndividual(
        int $lecturaId,
        float $lecturaActual,
        ?string $observaciones = null
    ): LecturaMedidor {
        return DB::transaction(function () use ($lecturaId, $lecturaActual, $observaciones) {
            $lectura = LecturaMedidor::with('periodo', 'unidad')->findOrFail($lecturaId);

            if ($lectura->periodo->estado !== 'abierto') {
                throw new \Exception("El período {$lectura->periodo->nombre_periodo} está cerrado. No se pueden editar lecturas.");
            }

            // [REMEDIACIÓN-2.3] No editar lecturas de un período si ya existe
            // una lectura registrada en un período posterior: el next-period
            // guarda su lectura_anterior como snapshot y la edición dejaría la
            // cadena inconsistente (y el prorrateo con base vencida).
            $hayLecturasPosteriores = LecturaMedidor::where('unidad_id', $lectura->unidad_id)
                ->where('periodo_id', '>', $lectura->periodo_id)
                ->exists();

            if ($hayLecturasPosteriores) {
                throw new \Exception('No se puede editar esta lectura: ya existe una lectura registrada en un período posterior para la unidad.');
            }

            $valorLecturaAnterior = (float) $lectura->lectura_anterior;
            $this->validarLecturaActualMayorAnterior($lecturaActual, $valorLecturaAnterior);

            $consumoM3 = $lecturaActual - $valorLecturaAnterior;

            $valoresAnteriores = [
                'lectura_actual' => $lectura->lectura_actual,
                'consumo_m3' => $lectura->consumo_m3,
            ];

            $lectura->update([
                'lectura_actual' => $lecturaActual,
                'consumo_m3' => $consumoM3,
                'observaciones' => $observaciones,
            ]);

            $this->registrarActivityLog(
                'lectura.actualizada',
                "Lectura individual actualizada: {$lectura->unidad->nombre_completo} - Consumo: {$consumoM3} M³",
                $lectura,
                [
                    'unidad' => $lectura->unidad->nombre_completo,
                    'valores_anteriores' => $valoresAnteriores,
                    'lectura_actual' => $lecturaActual,
                    'consumo_m3' => $consumoM3,
                ]
            );

            Log::info('Lectura individual actualizada', [
                'lectura_id' => $lectura->id,
                'periodo_id' => $lectura->periodo_id,
                'consumo_m3' => $consumoM3,
                'usuario' => auth()->user()?->id,
            ]);

            return $lectura->fresh();
        });
    }

    /**
     * Actualizar una lectura compartida (grupo) existente
     *
     * Permite editar la lectura del grupo y la distribución de consumo entre unidades.
     *
     * @param int $lecturaGrupoId ID del registro en lectura_grupos_periodos
     * @param float $lecturaActual Nueva lectura actual del medidor compartido
     * @param array $distribucionManual Array asociativo [unidad_id => consumo_asignado]
     * @param string|null $observaciones Observaciones opcionales
     * @return LecturaGrupoPeriodo
     * @throws \Exception
     */
    public function actualizarLecturaCompartida(
        int $lecturaGrupoId,
        float $lecturaActual,
        array $distribucionManual,
        ?string $observaciones = null
    ): LecturaGrupoPeriodo {
        return DB::transaction(function () use ($lecturaGrupoId, $lecturaActual, $distribucionManual, $observaciones) {
            $lecturaGrupo = LecturaGrupoPeriodo::with(['periodo', 'medidorGrupo.unidades'])
                ->findOrFail($lecturaGrupoId);

            if ($lecturaGrupo->periodo->estado !== 'abierto') {
                throw new \Exception("El período {$lecturaGrupo->periodo->nombre_periodo} está cerrado. No se pueden editar lecturas.");
            }

            // [REMEDIACIÓN-2.3] Mismo guard de cadena que la lectura individual:
            // si el grupo ya tiene lecturas distribuidas en períodos posteriores,
            // editar el total dejaría esas distribuciones con base vencida.
            $hayLecturasPosteriores = LecturaGrupoPeriodo::where('medidor_grupo_id', $lecturaGrupo->medidor_grupo_id)
                ->where('periodo_id', '>', $lecturaGrupo->periodo_id)
                ->exists();

            if ($hayLecturasPosteriores) {
                throw new \Exception('No se puede editar esta lectura: el grupo ya tiene lecturas registradas en períodos posteriores.');
            }

            $valorLecturaAnterior = (float) $lecturaGrupo->lectura_anterior;
            $this->validarLecturaActualMayorAnterior($lecturaActual, $valorLecturaAnterior);

            $consumoTotalCalculado = $lecturaActual - $valorLecturaAnterior;

            // Validar y normalizar la distribución manual
            $unidades = $lecturaGrupo->medidorGrupo->unidades->where('activo', true);
            $distribucionValidada = $this->normalizarDistribucion(
                $distribucionManual,
                $unidades,
                $consumoTotalCalculado
            );

            // Actualizar registro del grupo
            $lecturaGrupo->update([
                'lectura_actual' => $lecturaActual,
                'consumo_total' => $consumoTotalCalculado,
                'observaciones' => $observaciones,
            ]);

            // Actualizar lecturas distribuidas de cada unidad
            foreach ($distribucionValidada as $unidadId => $consumoAsignado) {
                LecturaMedidor::where('periodo_id', $lecturaGrupo->periodo_id)
                    ->where('medidor_grupo_id', $lecturaGrupo->medidor_grupo_id)
                    ->where('unidad_id', $unidadId)
                    ->update([
                        'consumo_m3' => $consumoAsignado,
                        'observaciones' => $observaciones,
                    ]);
            }

            $this->registrarActivityLog(
                'lectura.compartida_actualizada',
                "Lectura compartida actualizada: Grupo '{$lecturaGrupo->medidorGrupo->nombre}' - Consumo total: {$consumoTotalCalculado} M³",
                $lecturaGrupo,
                [
                    'grupo' => $lecturaGrupo->medidorGrupo->nombre,
                    'grupo_id' => $lecturaGrupo->medidor_grupo_id,
                    'lectura_actual' => $lecturaActual,
                    'consumo_total_m3' => $consumoTotalCalculado,
                    'distribucion' => $distribucionValidada,
                ]
            );

            Log::info('Lectura compartida actualizada', [
                'lectura_grupo_id' => $lecturaGrupo->id,
                'grupo_id' => $lecturaGrupo->medidor_grupo_id,
                'periodo_id' => $lecturaGrupo->periodo_id,
                'consumo_total_m3' => $consumoTotalCalculado,
                'usuario' => auth()->user()?->id,
            ]);

            return $lecturaGrupo->fresh();
        });
    }

    /**
     * Normalizar y validar la distribución manual de consumo entre unidades
     *
     * Reglas:
     * - Toda unidad del grupo debe estar presente.
     * - Valores no negativos.
     * - Si la suma coincide con el consumo total (diferencia < 0.01), se respeta.
     * - Si la diferencia es pequeña (hasta 0.10 M³), se absorbe en la ÚLTIMA unidad.
     *   (coincide con lo que muestra el botón "Repartir equitativamente" del JS).
     * - Si la diferencia es grande, se ajusta proporcionalmente y el residuo final
     *   se echa a la última unidad.
     *
     * @param array $distribucionManual [unidad_id => consumo]
     * @param \Illuminate\Support\Collection $unidades
     * @param float $consumoTotal
     * @return array [unidad_id => consumo_normalizado]
     * @throws \Exception
     */
    private function normalizarDistribucion(array $distribucionManual, $unidades, float $consumoTotal): array
    {
        if ($unidades->isEmpty()) {
            throw new \Exception("El grupo no tiene unidades activas.");
        }

        $resultado = [];
        $suma = 0.0;
        $idsOrdenados = [];

        foreach ($unidades as $unidad) {
            $valor = isset($distribucionManual[$unidad->id])
                ? (float) str_replace(',', '.', $distribucionManual[$unidad->id])
                : 0.0;

            if ($valor < 0) {
                throw new \Exception("El consumo asignado a {$unidad->nombre_completo} no puede ser negativo.");
            }

            $resultado[$unidad->id] = round($valor, 2);
            $suma += $resultado[$unidad->id];
            $idsOrdenados[] = $unidad->id;
        }

        $diferencia = round($consumoTotal - $suma, 2);

        // Si la diferencia es nula (dentro de tolerancia), respetar como está
        if (abs($diferencia) <= 0.009) {
            return $resultado;
        }

        // Diferencia pequeña (hasta 0.10 M³): absorber directamente en la última unidad
        if (abs($diferencia) <= 0.10) {
            $ultimaUnidadId = end($idsOrdenados);
            $resultado[$ultimaUnidadId] = round($resultado[$ultimaUnidadId] + $diferencia, 2);

            // Si queda negativo por la corrección, evitarlo (repartir el ajuste)
            if ($resultado[$ultimaUnidadId] < 0) {
                $resultado[$ultimaUnidadId] = round($resultado[$ultimaUnidadId] - $diferencia, 2);
                // Ajuste proporcional como fallback
                return $this->ajusteProporcional($resultado, $idsOrdenados, $consumoTotal);
            }

            return $resultado;
        }

        // Diferencia grande: ajuste proporcional
        return $this->ajusteProporcional($resultado, $idsOrdenados, $consumoTotal);
    }

    /**
     * Ajuste proporcional de la distribución + residuo en la última unidad.
     */
    private function ajusteProporcional(array $resultado, array $idsOrdenados, float $consumoTotal): array
    {
        $suma = array_sum($resultado);
        $factor = $suma > 0 ? ($consumoTotal / $suma) : 1.0;

        $ajustado = [];
        $nuevaSuma = 0.0;
        foreach ($idsOrdenados as $id) {
            $nuevoValor = round($resultado[$id] * $factor, 2);
            $ajustado[$id] = $nuevoValor;
            $nuevaSuma += $nuevoValor;
        }

        // Echar el residuo final a la última unidad
        $residuo = round($consumoTotal - $nuevaSuma, 2);
        if (abs($residuo) > 0.009) {
            $ultimaUnidadId = end($idsOrdenados);
            $ajustado[$ultimaUnidadId] = round($ajustado[$ultimaUnidadId] + $residuo, 2);
        }

        return $ajustado;
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
        // [REMEDIACIÓN-2.3] whereNotNull(lectura_actual): excluye las filas
        // DISTRIBUIDAS de medidor compartido (lectura_anterior/actual NULL).
        // Antes, si la unidad era individual tras salir de un grupo, la fila
        // huérfana matcheaba y su NULL se convertía en base 0.0 => se cobraba
        // el volumen absoluto completo del medidor como un solo mes.
        return LecturaMedidor::where('unidad_id', $unidadId)
            ->whereNotNull('lectura_actual')
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
