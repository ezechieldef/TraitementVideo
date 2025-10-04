<?php

namespace Database\Seeders;

use App\Models\Prompte;
use Illuminate\Database\Seeder;

class PrompteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Global default promptes (visible to everyone, no entité)
        $promptes = [
            // FR - SECTION
            [
                'titre' => 'Découper la vidéo en sections (par défaut)',
                'type' => 'SECTION',
                'contenu' => "Analyse la vidéo et propose un découpage en sections cohérentes.\nObjectifs:\n- Identifier les changements de sujet, de chapitre ou d'étape.\n- Donner un titre court (<= 80 caractères) à chaque section.\n- Résumer en 2-3 phrases le contenu clé de chaque section.\nContraintes:\n- Rester fidèle au propos de la vidéo.\n- Éviter le hors-sujet et les informations non vérifiées.",
                'categorie' => '',
                'langue' => 'fr',
                'is_default' => true,
                'visible' => true,
            ],
            // FR - RESUME
            [
                'titre' => 'Résumé de la vidéo (par défaut)',
                'type' => 'RESUME',
                'contenu' => "Produis un résumé clair et structuré de la vidéo.\nObjectifs:\n- Fournir un paragraphe synthétique du contenu.\n- Lister 3 à 5 points clés.\n- Conclure par une phrase de synthèse.\nContraintes:\n- Rester neutre et factuel.\n- Ne pas inventer d'informations.",
                'categorie' => '',
                'langue' => 'fr',
                'is_default' => true,
                'visible' => true,
            ],
            // EN - SECTION
            [
                'titre' => 'Video sectioning (default)',
                'type' => 'SECTION',
                'contenu' => "Analyze the video and propose a coherent section breakdown.\nGoals:\n- Detect topic/step/segment changes.\n- Provide a short title (<= 80 chars) for each section.\n- Add a concise 2–3 sentence summary per section.\nConstraints:\n- Stay faithful to the video content.\n- Avoid speculation and unverifiable details.",
                'categorie' => '',
                'langue' => 'en',
                'is_default' => true,
                'visible' => true,
            ],
            // EN - RESUME
            [
                'titre' => 'Video summarization (default)',
                'type' => 'RESUME',
                'contenu' => "Produce a clear, well-structured summary of the video.\nGoals:\n- Provide a concise paragraph overview.\n- List 3–5 key points.\n- End with a one-sentence conclusion.\nConstraints:\n- Remain neutral and factual.\n- Do not fabricate information.",
                'categorie' => '',
                'langue' => 'en',
                'is_default' => true,
                'visible' => true,
            ],
        ];

        foreach ($promptes as $data) {
            Prompte::updateOrCreate(
                [
                    'titre' => $data['titre'],
                    'entite_id' => null,
                ],
                [
                    'type' => $data['type'],
                    'contenu' => $data['contenu'],
                    'categorie' => $data['categorie'],
                    'langue' => $data['langue'],
                    'is_default' => (bool) ($data['is_default'] ?? false),
                    'visible' => (bool) ($data['visible'] ?? true),
                    'entite_id' => null,
                ]
            );
        }
    }
}
