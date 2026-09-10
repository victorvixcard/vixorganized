<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectTemplate;
use Illuminate\Database\Seeder;

/**
 * Projetos de exemplo para ver o sistema funcionando.
 * Rode com: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $felipe = User::where('name', 'Felipe')->first() ?? User::first();

        $samples = [
            ['name' => 'Projeto exemplo A', 'status' => 'em_andamento', 'done' => 7,  'due' => now()->addDays(30)],
            ['name' => 'Projeto exemplo B', 'status' => 'em_andamento', 'done' => 3,  'due' => now()->addDays(45)],
            ['name' => 'Projeto exemplo C', 'status' => 'aguardando',   'done' => 0,  'due' => null],
            ['name' => 'Projeto exemplo D', 'status' => 'pausado',      'done' => 5,  'due' => now()->subDays(3)],
        ];

        foreach ($samples as $s) {
            $project = Project::create([
                'name' => $s['name'],
                'description' => 'Projeto de demonstração. Edite ou exclua.',
                'status' => $s['status'],
                'owner_id' => $felipe?->id,
                'due_date' => $s['due'],
            ]);
            ProjectTemplate::apply($project);

            // Marca os primeiros N passos como feitos, na ordem das fases.
            $remaining = $s['done'];
            foreach ($project->phases as $phase) {
                foreach ($phase->steps as $step) {
                    if ($remaining <= 0) {
                        break 2;
                    }
                    $step->update(['is_done' => true, 'done_at' => now()->subDays($remaining), 'done_by' => $felipe?->id]);
                    $remaining--;
                }
                $phase->syncCompletion();
            }
            $project->syncCurrentPhase();
            $project->log('criou', 'Projeto de demonstração criado', $felipe?->id);
        }

        Project::normalizeRanks();
    }
}
