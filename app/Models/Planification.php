<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planification extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entite_id',
        'chaine_id',
        'type',
        'repeterChaque',
        'next_execution_at',
        'last_execution_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'repeterChaque' => 'integer',
            'next_execution_at' => 'datetime',
            'last_execution_at' => 'datetime',
        ];
    }

    public function entite(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }

    public function chaine(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Chaine::class);
    }
}
