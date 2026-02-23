<?php

namespace App\Services;

use App\Models\FacturaAguaConfig;
use App\Models\FacturaAguaHistorial;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacturaAguaService
{
    /**
     * Obtener todas las facturas de agua configuradas.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerFacturasConfiguradas()
    {
        return FacturaAguaConfig::with('creadoPor')
            ->activos()
            ->orderBy('categoria')
            ->orderBy('tipo')
            ->get();
    }

    /**
     * Obtener una factura config por su tipo.
     *
     * @param string $tipo 'domiciliaria_1', 'domiciliaria_2', 'comercial'
     * @return FacturaAguaConfig|null
     */
    public function obtenerFacturaPorTipo(string $tipo): ?FacturaAguaConfig
    {
        return FacturaAguaConfig::porTipo($tipo)
            ->activos()
            ->first();
    }

    /**
     * Obtener facturas por categoría.
     *
     * @param string $categoria 'domiciliaria' o 'comercial'
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerFacturasPorCategoria(string $categoria)
    {
        return FacturaAguaConfig::porCategoria($categoria)
            ->activos()
            ->get();
    }

    /**
     * Actualizar el número de factura con motivo obligatorio.
     *
     * @param int $facturaId
     * @param string $nuevoNumero
     * @param string $motivo
     * @return FacturaAguaConfig
     * @throws \Exception
     */
    public function actualizarNumeroFactura(int $facturaId, string $nuevoNumero, string $motivo): FacturaAguaConfig
    {
        // Validaciones
        $nuevoNumero = trim($nuevoNumero);
        if (empty($nuevoNumero)) {
            throw new \Exception('El número de factura no puede estar vacío');
        }

        if (empty(trim($motivo))) {
            throw new \Exception('El motivo del cambio es obligatorio');
        }

        $factura = FacturaAguaConfig::find($facturaId);
        if (!$factura) {
            throw new \Exception('Factura no encontrada');
        }

        $numeroAnterior = $factura->numero_factura;

        if ($numeroAnterior === $nuevoNumero) {
            throw new \Exception('El nuevo número de factura es igual al actual');
        }

        return DB::transaction(function () use ($factura, $nuevoNumero, $numeroAnterior, $motivo) {
            // Actualizar número de factura
            $factura->numero_factura = $nuevoNumero;
            $factura->save();

            // Guardar en historial
            FacturaAguaHistorial::create([
                'factura_agua_config_id' => $factura->id,
                'numero_factura_anterior' => $numeroAnterior,
                'numero_factura_nuevo' => $nuevoNumero,
                'cambiado_por' => auth()->id(),
                'motivo' => $motivo,
            ]);

            // Registrar en log de actividad
            if (class_exists('App\Models\ActivityLog')) {
                \App\Models\ActivityLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'factura_agua.actualizada',
                    'model_type' => FacturaAguaConfig::class,
                    'model_id' => $factura->id,
                    'description' => "Número de factura {$factura->tipo} cambiado de {$numeroAnterior} a {$nuevoNumero}",
                    'properties' => [
                        'tipo' => $factura->tipo,
                        'numero_anterior' => $numeroAnterior,
                        'numero_nuevo' => $nuevoNumero,
                        'motivo' => $motivo,
                    ],
                ]);
            }

            Log::info("Número de factura actualizado", [
                'tipo' => $factura->tipo,
                'anterior' => $numeroAnterior,
                'nuevo' => $nuevoNumero,
                'usuario' => auth()->id(),
            ]);

            return $factura;
        });
    }

    /**
     * Crear una factura config inicial (para seeders).
     *
     * @param string $tipo
     * @param string $numeroFactura
     * @param string $categoria
     * @return FacturaAguaConfig
     */
    public function crearFacturaInicial(string $tipo, string $numeroFactura, string $categoria): FacturaAguaConfig
    {
        return FacturaAguaConfig::create([
            'tipo' => $tipo,
            'numero_factura' => $numeroFactura,
            'categoria' => $categoria,
            'activo' => true,
            'creado_por' => auth()->id() ?? 1,
        ]);
    }

    /**
     * Obtener historial de cambios de facturas.
     *
     * @param int|null $facturaId
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function obtenerHistorial(?int $facturaId = null, int $limit = 50)
    {
        $query = FacturaAguaHistorial::with(['facturaAguaConfig', 'cambiadoPor'])
            ->orderBy('created_at', 'desc');

        if ($facturaId) {
            $query->where('factura_agua_config_id', $facturaId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Verificar si existen las 3 facturas configuradas.
     *
     * @return bool
     */
    public function verificarFacturasConfiguradas(): bool
    {
        $tiposRequeridos = ['domiciliaria_1', 'domiciliaria_2', 'comercial'];
        $facturasExistentes = FacturaAguaConfig::activos()
            ->whereIn('tipo', $tiposRequeridos)
            ->pluck('tipo')
            ->toArray();

        return count($facturasExistentes) === count($tiposRequeridos);
    }

    /**
     * Obtener las 3 facturas principales para un período.
     *
     * @return array
     */
    public function obtenerFacturasPrincipales(): array
    {
        return [
            'domiciliaria_1' => $this->obtenerFacturaPorTipo('domiciliaria_1'),
            'domiciliaria_2' => $this->obtenerFacturaPorTipo('domiciliaria_2'),
            'comercial' => $this->obtenerFacturaPorTipo('comercial'),
        ];
    }
}
