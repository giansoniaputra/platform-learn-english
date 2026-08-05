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
     *
     * Kosakata & percakapan tidak lagi di-seed statis — semua kontennya
     * digenerate lewat AI dari dashboard (lihat KeywordController).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'rizky@sepuluh.app'],
            [
                'name' => 'Rizky',
                'password' => Hash::make('belajarlagi'),
                'email_verified_at' => now(),
            ],
        );
    }
}
