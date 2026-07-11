<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Faculty>
 */
class FacultyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('FAC??')),
            'name_th' => 'คณะ'.fake()->unique()->word(),
            'name_en' => 'Faculty of '.fake()->word(),
        ];
    }
}
