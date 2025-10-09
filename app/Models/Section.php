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
        'langue',
        'titre',
        'debut',
        'fin',
        'longueur',
        'transcription',
        'custom_instruction',
        'isFromCron',
        'is_processing',
        'error_message',
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
            'is_processing' => 'boolean',
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
