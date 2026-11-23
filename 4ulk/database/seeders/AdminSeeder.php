<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@4u.lk');
        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = env('ADMIN_PASSWORD') ?: Str::password(14, symbols: false);

        $admin = new User([
            'name' => '4U.lk Admin',
            'email' => $email,
            'password' => $password,
            'phone' => '0110000000',
            'district' => 'Colombo',
            'account_type' => 'business',
        ]);
        $admin->forceFill(['role' => 'admin', 'verified_at' => now(), 'trust_score' => 100])->save();

        $this->command?->warn("Admin account: {$email} / {$password}  (change this password after signing in)");
    }
}
