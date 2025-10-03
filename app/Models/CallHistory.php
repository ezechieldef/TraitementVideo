<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallHistory extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'keytoken_id',
        'user_id',
        'endpoint',
        'status_code',
        'latency_ms',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'latency_ms' => 'integer',
        ];
    }

    public function keyToken(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        // Migration constrains to 'keys_tokens' which looks like a typo; the FK column is keytoken_id
        return $this->belongsTo(KeyToken::class, 'keytoken_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
