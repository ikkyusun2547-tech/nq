<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_id' => Activity::factory(),
            'checkin_method' => 'realtime',
            'checkin_time' => now(),
            'student_lat' => fake()->latitude(14, 15),
            'student_lng' => fake()->longitude(103, 104),
            'distance_meters' => fake()->numberBetween(0, 50),
            'device_uuid' => fake()->uuid(),
            'photo_path' => 'attendance-selfies/fake.jpg',
            'status' => 'auto_approved',
        ];
    }

    public function flagged(string $reason = 'GPS_OUT_OF_BOUNDS'): static
    {
        return $this->state(fn () => ['status' => 'flagged', 'flag_reason' => $reason]);
    }
}
