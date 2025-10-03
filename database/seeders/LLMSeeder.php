<?php

namespace Database\Seeders;

use App\Models\LLM;
use Illuminate\Database\Seeder;

class LLMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // LLM::where('id', '>', 0)->delete();
        $datas = [

            [
                'nom' => 'gemini-2.5-flash (gratuit)',
                'model_version' => '2.5-flash',
            ],
            [
                'nom' => 'gemini-2.5-pro',
                'model_version' => '2.5-pro',
            ],
        ];
        foreach ($datas as $data) {
            \App\Models\LLM::updateOrCreate(
                ['nom' => $data['nom']],
                ['model_version' => $data['model_version']]
            );
        }
    }
}
