<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'video_id',
        'ordre',
        'titre',
        'debut',
        'fin',
        'longueur',
        'transcription',
        'custom_instruction',
        'isFromCron',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
            'debut' => 'integer',
            'fin' => 'integer',
            'longueur' => 'integer',
            'isFromCron' => 'boolean',
        ];
    }

    public function video(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function resumes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Resume::class);
    }
}
