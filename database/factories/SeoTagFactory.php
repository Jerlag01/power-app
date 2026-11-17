<?php

namespace Database\Factories;

use App\Models\SeoTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoTag>
 */
class SeoTagFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->words(3, true),
            'type' => 'meta',
            'key' => 'keywords',
            'value' => implode(', ', fake()->words(4)),
            'path' => null,
            'locale' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
