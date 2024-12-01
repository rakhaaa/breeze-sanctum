<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory(5)
            ->hasTodos(10)
            ->create();
    }
}
