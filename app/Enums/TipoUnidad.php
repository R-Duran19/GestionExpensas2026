<?php

namespace App\Enums;

enum TipoUnidad: string
{
    case DEPARTAMENTO = 'departamento';
    case OFICINA = 'oficina';
    case LOCAL = 'local';
    case PARQUEO = 'parqueo';
    case BAULERA = 'baulera';

    /**
     * Obtener label para mostrar en vistas
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DEPARTAMENTO => 'Departamento',
            self::OFICINA => 'Oficina',
            self::LOCAL => 'Local Comercial',
            self::PARQUEO => 'Estacionamiento',
            self::BAULERA => 'Baulera',
        };
    }

    /**
     * Obtener categoría permitida para este tipo
     */
    public function getCategoriaPermitida(): CategoriaUnidad
    {
        return match($this) {
            self::DEPARTAMENTO, self::PARQUEO, self::BAULERA => CategoriaUnidad::DOMICILIARIA,
            self::OFICINA, self::LOCAL => CategoriaUnidad::COMERCIAL,
        };
    }

    /**
     * Verificar si este tipo tiene medidor de agua
     */
    public function getTieneMedidor(): bool
    {
        return match($this) {
            self::PARQUEO, self::BAULERA => false,
            default => true,
        };
    }

    /**
     * Obtener color para UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::DEPARTAMENTO => 'blue',
            self::OFICINA => 'purple',
            self::LOCAL => 'orange',
            self::PARQUEO => 'green',
            self::BAULERA => 'gray',
        };
    }

    /**
     * Obtener icono para UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::DEPARTAMENTO => 'home',
            self::OFICINA => 'building',
            self::LOCAL => 'store',
            self::PARQUEO => 'car',
            self::BAULERA => 'box',
        };
    }

    /**
     * Mapeo desde el sistema anterior (tipo_propiedad_id)
     * 1 = Parqueo
     * 2 = Baulera
     * 3 = Local
     * 4 = Oficina
     * 5 = Departamento
     */
    public static function fromTipoPropiedadId(int $id): self
    {
        return match($id) {
            5 => self::DEPARTAMENTO,
            4 => self::OFICINA,
            3 => self::LOCAL,
            1 => self::PARQUEO,
            2 => self::BAULERA,
            default => throw new \InvalidArgumentException("Tipo propiedad ID {$id} no válido"),
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
