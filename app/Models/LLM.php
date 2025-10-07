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

    public function nomModel(): string
    {
        $t = str_replace(' ', '', trim(strtolower($this->nom)));
        $t = str_replace('_', '-', $t);
        $t = str_replace(['(', ')'], '', $t);
        $t = str_replace(['gratuit', 'free'], '', $t);

        return $t;
    }

    public function getType(): string
    {
        $type = $this->nom;
        $tab = [
            'GOOGLE' => ['google', 'gemini', 'bard'],
            'OPENAI' => ['openai', 'gpt'],

        ];
        foreach ($tab as $key => $values) {
            foreach ($values as $v) {
                if (stripos($type, $v) !== false) {
                    $type = $key;
                    break 2;
                }
            }
        }

        return strtoupper($type);
    }
}
