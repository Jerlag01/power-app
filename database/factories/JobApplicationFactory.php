<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\Vacancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vacancy_id' => Vacancy::factory(),
            'vacancy_title' => fn (array $attributes): string => (string) Vacancy::find($attributes['vacancy_id'])?->translate('title'),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'message' => fake()->paragraph(),
            'cv_path' => 'job-applications/'.fake()->uuid().'.pdf',
            'cv_name' => 'cv.pdf',
            'locale' => 'nl',
        ];
    }
}
