<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MajorSeeder::class);

        User::factory()->create([
            'name' => 'Super Admin',
            'name_thai' => 'ผู้ดูแลระบบ',
            'email' => 'admin@srru.ac.th',
            'role' => 'super_admin',
        ]);
    }
}
