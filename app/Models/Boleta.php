<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boleta extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla (Laravel no pluraliza correctamente español)
     */
    protected $table = 'boletas';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'expensa_id',
        'archivo',
        'ruta',
        'generada_en',
        'generada_por',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'generada_en' => 'datetime',
    ];

    /**
     * Get the expensa for this boleta.
     */
    public function expensa(): BelongsTo
    {
        return $this->belongsTo(Expensa::class, 'expensa_id');
    }

    /**
     * Get the user that generated the boleta.
     */
    public function generadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generada_por');
    }

    /**
     * Get the full path to the PDF file.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->ruta);
    }

    /**
     * Check if the PDF file exists.
     */
    public function existeArchivo(): bool
    {
        return file_exists($this->full_path);
    }
}
