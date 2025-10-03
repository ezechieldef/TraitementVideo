<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entite_id',
        'email',
        'role',
        'token',
        'status',
        'invited_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    public function entite(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }

    public static function createPending(Entite $entite, string $email, int $invitedByUserId, string $role = 'MEMBER'): self
    {
        return static::create([
            'entite_id' => $entite->id,
            'email' => $email,
            'role' => $role,
            'token' => bin2hex(random_bytes(24)),
            'status' => 'PENDING',
            'invited_by' => $invitedByUserId,
        ]);
    }
}
