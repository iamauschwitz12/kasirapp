<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'plastik_admin@gmail.com',
            'password' => Hash::make('admin123!@#'), // ganti dengan password Anda
            'role' => 'admin', // sesuaikan dengan nama kolom role Anda
            // 'branch_id' => 1, // jika sudah ada sistem cabang nanti
        ]);

        $this->call([
            UnitSatuanSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
