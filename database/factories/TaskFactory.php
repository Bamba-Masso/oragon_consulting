<?php

namespace Database\Factories;

use App\Models\Folder;
use App\Models\Priority;
use App\Models\Status;
use App\Models\StatusType;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Les statuts et priorités doivent déjà exister (StatusSeeder, PrioritySeeder).
 *
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'folder_id' => Folder::factory(),
            'status_id' => fn (): ?string => Status::ofType(StatusType::TASK)->where('code', 'pending')->value('id'),
            'priority_id' => fn (): ?string => Priority::where('name', 'normal')->value('id'),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }

    /**
     * Assigne la tâche à un collaborateur.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $user->id,
        ]);
    }
}
