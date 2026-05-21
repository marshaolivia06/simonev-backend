<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'email'    => 'admin@simonev.com',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
            'status'   => 'approved', // ← fix: sesuai ENUM di database
        ]);
    }
}