<?php

namespace Database\Factories;

use App\Models\Certificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['VCA**', 'VCA*', 'ISO 9001', 'ISO 14001', 'AREI-erkend', 'Erkend aannemer', 'Volta']).' '.fake()->unique()->numerify('##'),
            'issuer' => fake()->company(),
            'number' => fake()->numerify('CERT-######'),
            'valid_until' => now()->addYear(),
            'description' => ['nl' => fake()->sentence(), 'fr' => fake()->sentence(), 'en' => fake()->sentence()],
            'is_visible' => true,
            'sort_order' => 0,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => ['is_visible' => false]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => ['valid_until' => now()->subDay()]);
    }
}
