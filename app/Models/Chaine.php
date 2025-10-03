<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chaine extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entite_id',
        'titre',
        'channel_id',
        'youtube_url',
    ];

    public function entite(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }

    public function planifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Planification::class);
    }

    public function cronExecutions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CronExecution::class);
    }
}
