<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Organization;
use App\Models\Priority;
use App\Models\Status;
use App\Models\StatusType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Les statuts et priorités doivent déjà exister (StatusSeeder, PrioritySeeder).
 *
 * @extends Factory<Folder>
 */
class FolderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'status_id' => fn (): ?string => Status::ofType(StatusType::FOLDER)->where('code', 'to_qualify')->value('id'),
            'priority_id' => fn (): ?string => Priority::where('name', 'normal')->value('id'),
            'reference' => fake()->unique()->numerify('DOS-2026-#####'),
            'title' => fake()->sentence(4),
            'mission_type' => fake()->randomElement(['Tenue comptable', 'Déclarations fiscales', 'Audit légal', 'Analyse financière']),
            'period' => '2026',
            'due_date' => fake()->dateTimeBetween('now', '+3 months'),
        ];
    }

    /**
     * Désigne le responsable du dossier.
     */
    public function managedBy(User $manager): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_manager_id' => $manager->id,
        ]);
    }

    /**
     * Affecte un collaborateur au dossier une fois celui-ci créé.
     */
    public function assignedTo(User $user, ?string $pole = null): static
    {
        return $this->afterCreating(fn (Folder $folder) => $folder->assignees()->attach($user, ['pole' => $pole]));
    }
}
