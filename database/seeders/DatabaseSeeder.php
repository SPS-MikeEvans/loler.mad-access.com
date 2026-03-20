<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@lolerkit.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'competent_person_flag' => true,
            'client_id' => null,
        ]);

        $this->call(KitTypeSeeder::class);
        $this->call(ChecklistSeeder::class);
    }
}
