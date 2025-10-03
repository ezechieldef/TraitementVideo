<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
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
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function membreEntites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MembreEntite::class);
    }

    public function entites(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Entite::class, 'membre_entites')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            // Avoid creating duplicates if somehow re-fired
            if ($user->membreEntites()->exists()) {
                return;
            }

            $titre = $user->name ?: ('Entite utilisateur '.$user->id);

            $entite = Entite::create([
                'titre' => $titre,
                'type' => 'INDIVIDUEL',
                // 'type_contenu' defaults to 'AUTRE' via migration
            ]);

            MembreEntite::create([
                'entite_id' => $entite->id,
                'user_id' => $user->id,
                'role' => 'OWNER',
            ]);

            // Plus de table 'invitations' - les invitations sont gérées via membre_entites (invite_status)
        });
    }
}
