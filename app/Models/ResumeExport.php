<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumeExport extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'format',
        'cible',
        'url_cible',
        'api_key',
        'statut',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }
}
