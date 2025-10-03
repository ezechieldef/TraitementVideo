<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entite extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'titre',
        'type',
        'type_contenu',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    public function membreEntites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MembreEntite::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'membre_entites')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function keyTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KeyToken::class);
    }

    public function chaines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Chaine::class);
    }

    public function planifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Planification::class);
    }

    public function cronExecutions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CronExecution::class);
    }

    public function videos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function promptes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Prompte::class);
    }
}
