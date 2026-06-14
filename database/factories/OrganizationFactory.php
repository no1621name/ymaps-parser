<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    protected $model = Organization::class;

    public function definition(): array
    {
        return [
            'business_id' => $this->faker->numerify('##########'),
            'name' => $this->faker->company(),
            'avg_rating' => $this->faker->randomFloat(2, 1, 5),
            'reviews_count' => $this->faker->numberBetween(10, 500),
            'ratings_count' => $this->faker->numberBetween(10, 500),
            'status' => OrganizationStatus::Pending,
            'error_message' => null,
            'parsed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationStatus::Pending,
        ]);
    }

    public function parsing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationStatus::Parsing,
        ]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationStatus::Done,
            'parsed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrganizationStatus::Failed,
            'error_message' => 'Test error',
        ]);
    }
}
