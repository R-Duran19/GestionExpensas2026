<?php

namespace App\Enums;

enum CategoriaUnidad: string
{
    case DOMICILIARIA = 'domiciliaria';
    case COMERCIAL = 'comercial';

    /**
     * Obtener label para mostrar en vistas
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DOMICILIARIA => 'Uso Domiciliario',
            self::COMERCIAL => 'Uso Comercial',
        };
    }

    /**
     * Obtener factor de prorrateo según categoría desde la BD
     * Los factores son CONFIGURABLES, no harcodeados
     */
    public function getFactorProrrateo(): float
    {
        // Buscar factor vigente en la base de datos
        $factor = \App\Models\FactorProrrateo::porCategoria($this->value)
            ->vigente($this->value)
            ->first();

        // Si no existe, usar valores por defecto para compatibilidad
        if (!$factor) {
            return match($this) {
                self::DOMICILIARIA => 2.1,
                self::COMERCIAL => 3.5,
            };
        }

        return (float) $factor->factor;
    }

    /**
     * Obtener color para UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::DOMICILIARIA => 'blue',
            self::COMERCIAL => 'orange',
        };
    }

    /**
     * Obtener todos los valores como array para select
     */
    public static function toSelectArray(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()])
            ->toArray();
    }
}
