<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyToken extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entite_id',
        'type',
        'llm_id',
        'value',
        'status',
        'usage_limit_count',
        'limit_periode_minutes',
        'isLimitExceded',
        'limitExceedAt',
        'last_used_at',
        'quota_used',
        'priority',
        'error_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'isLimitExceded' => 'boolean',
            'limitExceedAt' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function entite(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }

    public function llm(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(LLM::class, 'llm_id');
    }

    public function callHistories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CallHistory::class, 'keytoken_id');
    }
}
