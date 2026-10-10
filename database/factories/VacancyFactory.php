<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vacancy>
 */
class VacancyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->randomElement(['Elektricien', 'Projectleider', 'Calculator', 'Werfleider']).' '.fake()->unique()->numerify('###');

        return [
            'slug' => Str::slug($title),
            'title' => ['nl' => $title, 'fr' => $title, 'en' => $title],
            'intro' => ['nl' => fake()->sentence()],
            'description' => ['nl' => '<p>'.fake()->paragraph().'</p>'],
            'requirements' => ['nl' => '<ul><li>'.fake()->sentence().'</li></ul>'],
            'location' => fake()->city(),
            'employment_type' => EmploymentType::FullTime,
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => ['is_visible' => false]);
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => ['closes_at' => now()->subDay()]);
    }
}
