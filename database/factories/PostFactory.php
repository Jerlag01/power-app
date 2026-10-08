<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(4, true));

        return [
            'user_id' => User::factory(),
            'slug' => Str::slug($title),
            'title' => ['nl' => $title, 'fr' => $title.' (fr)', 'en' => $title.' (en)'],
            'excerpt' => ['nl' => fake()->sentence(), 'fr' => fake()->sentence(), 'en' => fake()->sentence()],
            'body' => [
                'nl' => '<p>'.fake()->paragraph().'</p>',
                'fr' => '<p>'.fake()->paragraph().'</p>',
                'en' => '<p>'.fake()->paragraph().'</p>',
            ],
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now()->addWeek(),
        ]);
    }
}
