<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prompte extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entite_id',
        'type',
        'categorie',
        'titre',
        'contenu',
        'langue',
        'is_default',
        'visible',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'visible' => 'boolean',
        ];
    }

    public function entite(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }
}
