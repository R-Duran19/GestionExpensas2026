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
     * Obtener factor de prorrateo según categoría
     * Domiciliaria: 2.1
     * Comercial: 3.5
     */
    public function getFactorProrrateo(): float
    {
        return match($this) {
            self::DOMICILIARIA => 2.1,
            self::COMERCIAL => 3.5,
        };
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
