<?php

namespace Database\Factories;

use App\Models\Faculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Major>
 */
class MajorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'code' => strtoupper(fake()->unique()->lexify('MAJ????')),
            'name_th' => 'สาขา'.fake()->unique()->word(),
            'name_en' => fake()->word().' Major',
            'degree_abbr' => fake()->randomElement(['B.Sc.', 'B.A.', 'B.Ed.']),
        ];
    }
}
