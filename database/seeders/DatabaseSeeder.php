<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cria os usuários iniciais. Senha padrão vem de SEED_PASSWORD no .env
 * (padrão "vixorganize"). Troque depois do primeiro login.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make(env('SEED_PASSWORD', 'vixorganize'));

        User::updateOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'vitor@vixorganize.local')],
            ['name' => env('SEED_ADMIN_NAME', 'Vitão'), 'password' => $password]
        );

        User::updateOrCreate(
            ['email' => env('SEED_MANAGER_EMAIL', 'felipe@vixorganize.local')],
            ['name' => env('SEED_MANAGER_NAME', 'Felipe'), 'password' => $password]
        );
    }
}
