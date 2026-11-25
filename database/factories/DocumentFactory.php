<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
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
            'organization_id' => fn (array $attributes): ?string => Folder::find($attributes['folder_id'])?->organization_id,
            'uploader_type' => 'user',
            'uploader_id' => User::factory(),
            'name' => fake()->sentence(3),
            'original_filename' => 'document.pdf',
            'file_path' => 'documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10_000, 2_000_000),
            'category' => fake()->randomElement(['Reçu', 'Bilan', 'Déclaration fiscale']),
            'period' => '2026',
        ];
    }

    /**
     * Document déjà envoyé au client.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published_to_client' => true,
            'published_at' => now(),
        ]);
    }
}
