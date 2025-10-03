<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LLM extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var string
     */
    protected $table = 'l_l_m_s';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'model_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    public function keyTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(KeyToken::class, 'llm_id');
    }
}
