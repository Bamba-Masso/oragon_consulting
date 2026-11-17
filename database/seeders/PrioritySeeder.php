<?php

namespace Database\Seeders;

use App\Models\Priority;
use Illuminate\Database\Seeder;

class PrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'low', 'label' => 'Faible', 'color' => '#16A34A', 'level' => 1],
            ['name' => 'normal', 'label' => 'Normale', 'color' => '#2563EB', 'level' => 2],
            ['name' => 'high', 'label' => 'Élevée', 'color' => '#D97706', 'level' => 3],
            ['name' => 'urgent', 'label' => 'Urgente', 'color' => '#DC2626', 'level' => 4],
        ] as $priority) {
            Priority::updateOrCreate(
                ['name' => $priority['name']],
                ['label' => $priority['label'], 'color' => $priority['color'], 'level' => $priority['level']],
            );
        }
    }
}
