<?php

namespace Database\Factories;

use App\Models\Expertise;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Expertise>
 */
class ExpertiseFactory extends Factory
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
            'slug' => Str::slug($title),
            'title' => ['nl' => $title, 'fr' => $title.' (fr)', 'en' => $title.' (en)'],
            'icon' => fake()->randomElement(Expertise::ICONS),
            'description' => ['nl' => fake()->paragraph(), 'fr' => fake()->paragraph(), 'en' => fake()->paragraph()],
            'services' => ['nl' => fake()->words(4), 'fr' => fake()->words(4), 'en' => fake()->words(4)],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
