<?php

namespace Database\Factories;

use App\Models\User;
use App\Services\AcademicYearCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'created_by' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'activity_level' => 'university',
            'activity_category' => fake()->randomElement(['culture', 'academic', 'sports', 'volunteer', 'ethics']),
            'activity_type' => 'elective',
            'academic_year' => AcademicYearCalculator::forDate(now()),
            'credit_hours' => fake()->numberBetween(1, 10),
            'capacity' => 100,
            'start_at' => $start,
            'end_at' => (clone $start)->modify('+2 hours'),
            'location_name' => fake()->address(),
            'location_lat' => fake()->latitude(14, 15),
            'location_lng' => fake()->longitude(103, 104),
            'allowed_radius' => 100,
            'qr_secret' => Str::random(32),
            'checkin_method' => 'realtime',
            'requires_gps' => true,
            'status' => 'open',
        ];
    }

    public function selfReport(): static
    {
        return $this->state(fn () => [
            'checkin_method' => 'self_report',
            'checkin_opens_at' => now()->subHour(),
            'checkin_closes_at' => now()->addHour(),
        ]);
    }

    public function noGpsRequired(): static
    {
        return $this->state(fn () => [
            'requires_gps' => false,
            'location_lat' => null,
            'location_lng' => null,
            'allowed_radius' => null,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => 'closed']);
    }
}
