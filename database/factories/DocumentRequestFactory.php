<?php

namespace Database\Factories;

use App\Models\DocumentRequest;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Les statuts doivent déjà exister (StatusSeeder).
 *
 * @extends Factory<DocumentRequest>
 */
class DocumentRequestFactory extends Factory
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
            'requested_by' => User::factory(),
            'reference' => fake()->unique()->numerify('DEM-2026-#####'),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status_id' => fn (): ?string => DocumentRequest::statusId(DocumentRequest::PENDING),
        ];
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => DocumentRequest::statusId(DocumentRequest::RECEIVED),
            'responded_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Ces pièces ont déjà été transmises.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => DocumentRequest::statusId(DocumentRequest::REJECTED),
            'responded_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => DocumentRequest::statusId(DocumentRequest::CLOSED),
            'responded_at' => now()->subDay(),
            'closed_at' => now(),
        ]);
    }
}
