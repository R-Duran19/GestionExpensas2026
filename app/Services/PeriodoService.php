<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\PeriodoExpensa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PeriodoService
{
    /**
     * Abrir un nuevo período de expensas
     *
     * @param int $mes Mes (1-12)
     * @param int $año Año (ej: 2025)
     * @return PeriodoExpensa
     * @throws \Exception
     */
    public function abrirPeriodo(int $mes, int $año): PeriodoExpensa
    {
        return DB::transaction(function () use ($mes, $año) {
            // Validar mes
            if ($mes < 1 || $mes > 12) {
                throw new \Exception('El mes debe estar entre 1 y 12');
            }

            // Validar año
            if ($año < 2000 || $año > 2100) {
                throw new \Exception('El año debe estar entre 2000 y 2100');
            }

            // Validar que no exista el período
            $periodoExistente = PeriodoExpensa::where('mes', $mes)
                ->where('año', $año)
                ->first();

            if ($periodoExistente) {
                throw new \Exception("Ya existe un período para {$mes}/{$año}");
            }

            // Validar secuencia de períodos
            $this->validarSecuenciaPeriodo($mes, $año);

            // Validar que el período anterior esté cerrado
            $this->validarPeriodoAnteriorCerrado($mes, $año);

            // Crear el período
            $periodo = PeriodoExpensa::create([
                'mes' => $mes,
                'año' => $año,
                'fecha_apertura' => now(),
                'estado' => 'abierto',
            ]);

            // Registrar en activity log
            $this->registrarActivityLog(
                'periodo.abierto',
                "Período {$periodo->nombre_periodo} abierto",
                $periodo
            );

            Log::info('Período abierto', [
                'periodo_id' => $periodo->id,
                'mes' => $mes,
                'año' => $año,
                'usuario' => auth()->user()?->id,
            ]);

            $this->invalidarCacheReportes();

            return $periodo;
        });
    }

    /**
     * Cerrar un período de expensas
     *
     * @param int $periodoId ID del período
     * @param string|null $motivo Motivo del cierre (opcional)
     * @return PeriodoExpensa
     * @throws \Exception
     */
    public function cerrarPeriodo(int $periodoId, ?string $motivo = null): PeriodoExpensa
    {
        return DB::transaction(function () use ($periodoId, $motivo) {
            $periodo = PeriodoExpensa::findOrFail($periodoId);

            // Validar que el período esté abierto
            if ($periodo->estado === 'cerrado') {
                throw new \Exception('El período ya está cerrado');
            }

            // Validar que haya expensas calculadas
            $expensasCount = \App\Models\Expensa::where('periodo_id', $periodoId)->count();
            if ($expensasCount === 0) {
                throw new \Exception('No hay expensas calculadas para este período. Debe calcular las expensas antes de cerrar.');
            }

            // Cerrar el período
            $periodo->update([
                'estado' => 'cerrado',
                'fecha_cierre' => now(),
                'cerrado_por' => auth()->id(),
                'motivo_reapertura' => null, // Limpiar motivo de reapertura si existía
            ]);

            // Registrar en activity log
            $this->registrarActivityLog(
                'periodo.cerrado',
                "Período {$periodo->nombre_periodo} cerrado" . ($motivo ? ": {$motivo}" : ''),
                $periodo,
                ['motivo' => $motivo, 'expensas_count' => $expensasCount]
            );

            Log::info('Período cerrado', [
                'periodo_id' => $periodo->id,
                'usuario' => auth()->user()?->id,
                'motivo' => $motivo,
                'expensas_count' => $expensasCount,
            ]);

            $this->invalidarCacheReportes();

            return $periodo->fresh();
        });
    }

    /**
     * Reabrir un período cerrado
     *
     * @param int $periodoId ID del período
     * @param string $motivo Motivo de la reapertura (obligatorio)
     * @return PeriodoExpensa
     * @throws \Exception
     */
    public function reabrirPeriodo(int $periodoId, string $motivo): PeriodoExpensa
    {
        return DB::transaction(function () use ($periodoId, $motivo) {
            $periodo = PeriodoExpensa::findOrFail($periodoId);

            // Validar que el período esté cerrado
            if ($periodo->estado === 'abierto') {
                throw new \Exception('Solo se pueden reabrir períodos que están cerrados');
            }

            // El motivo es obligatorio
            if (empty(trim($motivo))) {
                throw new \Exception('El motivo de reapertura es obligatorio');
            }

            // Reabrir el período
            $periodo->update([
                'estado' => 'abierto',
                'motivo_reapertura' => $motivo,
                'fecha_cierre' => null,
                'cerrado_por' => null,
            ]);

            // Registrar en activity log
            $this->registrarActivityLog(
                'periodo.reabierto',
                "Período {$periodo->nombre_periodo} reabierto: {$motivo}",
                $periodo,
                ['motivo_reapertura' => $motivo]
            );

            Log::info('Período reabierto', [
                'periodo_id' => $periodo->id,
                'usuario' => auth()->user()?->id,
                'motivo' => $motivo,
            ]);

            $this->invalidarCacheReportes();

            return $periodo->fresh();
        });
    }

    /**
     * Invalida el cache de reportes (los listados de períodos/años se cachean)
     */
    private function invalidarCacheReportes(): void
    {
        app(ReporteService::class)->invalidarCacheReportes();
    }

    /**
     * Validar que el período anterior esté cerrado
     *
     * @param int $mes Mes actual
     * @param int $año Año actual
     * @return bool True si está cerrado o es el primer período
     * @throws \Exception Si el período anterior está abierto
     */
    public function validarPeriodoAnteriorCerrado(int $mes, int $año): bool
    {
        // Si es enero, verificar diciembre del año anterior
        if ($mes === 1) {
            $añoAnterior = $año - 1;
            $periodoAnterior = PeriodoExpensa::where('mes', 12)
                ->where('año', $añoAnterior)
                ->first();

            if ($periodoAnterior && $periodoAnterior->estado === 'abierto') {
                throw new \Exception(
                    "No se puede abrir {$mes}/{$año}. " .
                    "El período anterior (Diciembre {$añoAnterior}) está abierto. " .
                    "Debe cerrarlo primero."
                );
            }

            return true;
        }

        // Verificar el mes anterior del mismo año
        $periodoAnterior = PeriodoExpensa::where('mes', $mes - 1)
            ->where('año', $año)
            ->first();

        if ($periodoAnterior && $periodoAnterior->estado === 'abierto') {
            throw new \Exception(
                "No se puede abrir {$mes}/{$año}. " .
                "El período anterior está abierto. " .
                "Debe cerrarlo primero."
            );
        }

        return true;
    }

    /**
     * Obtener el período abierto actual
     *
     * @return PeriodoExpensa|null
     */
    public function obtenerPeriodoAbierto(): ?PeriodoExpensa
    {
        return PeriodoExpensa::abiertos()
            ->orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->first();
    }

    /**
     * Obtener el período cerrado anterior
     *
     * @param int $mes Mes actual
     * @param int $año Año actual
     * @return PeriodoExpensa|null
     */
    public function obtenerPeriodoCerradoAnterior(int $mes, int $año): ?PeriodoExpensa
    {
        // Si es enero, buscar diciembre del año anterior
        if ($mes === 1) {
            return PeriodoExpensa::cerrados()
                ->where('mes', 12)
                ->where('año', $año - 1)
                ->first();
        }

        // Buscar el mes anterior del mismo año
        return PeriodoExpensa::cerrados()
            ->where('mes', $mes - 1)
            ->where('año', $año)
            ->first();
    }

    /**
     * Verificar si se puede abrir un nuevo período
     *
     * @param int $mes Mes (1-12)
     * @param int $año Año (ej: 2025)
     * @return array ['puede' => bool, 'motivo' => string]
     */
    public function puedeAbrirPeriodo(int $mes, int $año): array
    {
        try {
            // Validar que no exista
            $existente = PeriodoExpensa::where('mes', $mes)
                ->where('año', $año)
                ->first();

            if ($existente) {
                return [
                    'puede' => false,
                    'motivo' => "Ya existe un período para {$mes}/{$año}",
                ];
            }

            // Validar secuencia de períodos
            $this->validarSecuenciaPeriodo($mes, $año);

            // Validar período anterior
            $this->validarPeriodoAnteriorCerrado($mes, $año);

            return [
                'puede' => true,
                'motivo' => null,
            ];
        } catch (\Exception $e) {
            return [
                'puede' => false,
                'motivo' => $e->getMessage(),
            ];
        }
    }

    /**
     * Validar que el período a crear siga la secuencia correcta
     *
     * @param int $mes Mes a crear
     * @param int $año Año a crear
     * @return bool
     * @throws \Exception
     */
    private function validarSecuenciaPeriodo(int $mes, int $año): bool
    {
        // Obtener el último período creado (cualquier estado)
        $ultimoPeriodo = PeriodoExpensa::orderBy('año', 'desc')
            ->orderBy('mes', 'desc')
            ->first();

        // Si no hay ningún período,允许 crear cualquiera
        if (!$ultimoPeriodo) {
            return true;
        }

        // Calcular cuál debería ser el siguiente período
        $mesSiguiente = $ultimoPeriodo->mes + 1;
        $añoSiguiente = $ultimoPeriodo->año;

        // Si el último fue diciembre, el siguiente es enero del año siguiente
        if ($ultimoPeriodo->mes === 12) {
            $mesSiguiente = 1;
            $añoSiguiente = $ultimoPeriodo->año + 1;
        }

        // Validar que el período a crear sea el siguiente en la secuencia
        if ($mes !== $mesSiguiente || $año !== $añoSiguiente) {
            $nombreUltimo = $this->obtenerNombreMes($ultimoPeriodo->mes) . ' ' . $ultimoPeriodo->año;
            $nombreEsperado = $this->obtenerNombreMes($mesSiguiente) . ' ' . $añoSiguiente;
            $nombreIntento = $this->obtenerNombreMes($mes) . ' ' . $año;

            throw new \Exception(
                "El período debe seguir el orden cronológico. " .
                "El último período creado es {$nombreUltimo}. " .
                "El siguiente período debe ser {$nombreEsperado}, no {$nombreIntento}."
            );
        }

        return true;
    }

    /**
     * Obtener el nombre del mes en español
     *
     * @param int $mes Mes (1-12)
     * @return string
     */
    private function obtenerNombreMes(int $mes): string
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $meses[$mes] ?? 'Mes desconocido';
    }

    /**
     * Obtener estadísticas de un período
     *
     * @param int $periodoId ID del período
     * @return array
     */
    public function obtenerEstadisticas(int $periodoId): array
    {
        $periodo = PeriodoExpensa::with([
            'facturasAgua',
            'lecturasMedidor',
        ])->findOrFail($periodoId);

        $totalUnidades = \App\Models\Unidad::where('activo', true)
            ->where('tiene_medidor', true)
            ->count();

        $lecturasRegistradas = $periodo->lecturasMedidor()->count();

        return [
            'periodo' => $periodo->nombre_periodo,
            'estado' => $periodo->estado_label,
            'total_unidades_con_medidor' => $totalUnidades,
            'lecturas_registradas' => $lecturasRegistradas,
            'lecturas_pendientes' => max(0, $totalUnidades - $lecturasRegistradas),
            'progreso' => $totalUnidades > 0
                ? round(($lecturasRegistradas / $totalUnidades) * 100, 1)
                : 0,
            'facturas_registradas' => $periodo->facturasAgua()->count(),
            'facturas_esperadas' => 3, // domiciliaria_1, domiciliaria_2, comercial
        ];
    }

    /**
     * Registrar actividad en el log
     *
     * @param string $action Acción realizada
     * @param string $description Descripción
     * @param PeriodoExpensa $periodo Período
     * @param array $properties Propiedades adicionales
     * @return void
     */
    private function registrarActivityLog(
        string $action,
        string $description,
        PeriodoExpensa $periodo,
        array $properties = []
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'model_type' => get_class($periodo),
            'model_id' => $periodo->id,
            'properties' => array_merge([
                'mes' => $periodo->mes,
                'año' => $periodo->año,
                'estado' => $periodo->estado,
            ], $properties),
        ]);
    }
}
