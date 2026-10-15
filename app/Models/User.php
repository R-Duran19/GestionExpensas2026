<?php

namespace App\Models;

use App\Helpers\Pii;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    // Nota: SIN Loggable — cada login actualizaría last_login_at y spamearía
    // activity_logs. Las acciones del admin (crear acceso, reset) auditan
    // explícitamente vía ActivityLog::create en AccesoPortal.

    /**
     * Los tipos de usuario del sistema.
     */
    public const TIPOS = ['admin', 'propietario', 'inquilino'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tipo',
        'persona_id',
        'ci_hash',
        'activo',
        'must_change_password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'ci_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relaciones del portal [PORTAL / Fase 1.1]
    |--------------------------------------------------------------------------
    | Convención tipo/persona_id (igual que pagador_tipo/pagador_id de
    | ingresos, ADR 2026-08-04). persona_id apunta a la tabla según tipo.
    */

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Propietario::class, 'persona_id');
    }

    public function inquilino(): BelongsTo
    {
        return $this->belongsTo(Inquilino::class, 'persona_id');
    }

    /**
     * Persona vinculada (Propietario o Inquilino) según el tipo.
     * Null para admins.
     */
    public function getPersonaAttribute(): Propietario|Inquilino|null
    {
        return match ($this->tipo) {
            'propietario' => $this->propietario,
            'inquilino'   => $this->inquilino,
            default       => null,
        };
    }

    /**
     * Relación: Un usuario tiene muchos logs de actividad.
     *
     * @return HasMany
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers del portal
    |--------------------------------------------------------------------------
    */

    /**
     * ¿Es un usuario del portal (propietario o inquilino)?
     */
    public function esPortal(): bool
    {
        return in_array($this->tipo, ['propietario', 'inquilino'], true);
    }

    /**
     * ¿Es admin?
     */
    public function esAdmin(): bool
    {
        return $this->tipo === 'admin';
    }

    /**
     * Asignar el rol Spatie según el tipo (llamar tras crear el usuario).
     */
    public function sincronizarRol(): void
    {
        if (in_array($this->tipo, self::TIPOS, true)) {
            $this->syncRoles([$this->tipo]);
        }
    }

    /**
     * Registrar el CI para login (se guarda SOLO el hash, nunca en claro).
     */
    public function setCiLogin(string $ci): void
    {
        $this->ci_hash = Pii::hash($ci);
    }

    /**
     * Buscar usuario por CI exacto vía hash.
     */
    public static function buscarPorCiHash(string $ci): ?self
    {
        return self::where('ci_hash', Pii::hash($ci))->first();
    }
}
