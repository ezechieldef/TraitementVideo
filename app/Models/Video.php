<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'entite_id',
        'youtube_id',
        'titre',
        'url',
        'status',
        'thumbnails',
        'published_at',
        'duration',
        'langue',
        'step',
        'type_contenu',
        'section_instruction',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'duration' => 'integer',
            'step' => 'integer',
        ];
    }

    public function entite(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entite::class);
    }

    public function transcriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Transcription::class);
    }

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function resumes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Resume::class);
    }
}
