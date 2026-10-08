<?php

namespace Database\Factories;

use App\Models\Expertise;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'expertise_id' => Expertise::factory(),
            'slug' => Str::slug($title),
            'title' => ['nl' => $title, 'fr' => $title.' (fr)', 'en' => $title.' (en)'],
            'location' => fake()->city(),
            'summary' => ['nl' => fake()->sentence(), 'fr' => fake()->sentence(), 'en' => fake()->sentence()],
            'description' => ['nl' => fake()->paragraphs(2, true), 'fr' => fake()->paragraphs(2, true), 'en' => fake()->paragraphs(2, true)],
            'highlights' => [
                ['value' => (string) fake()->numberBetween(10, 999), 'label' => ['nl' => 'lichtpunten', 'fr' => 'points lumineux', 'en' => 'light points']],
            ],
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
