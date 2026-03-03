<?php

namespace Database\Seeders;

use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OwnerUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('OWNER_PASSWORD', 'VisaBor@2026!');

        DB::transaction(function () use ($password) {
            DB::statement("SET LOCAL app.is_superadmin = 'true'");

            User::updateOrCreate(
                ['email' => 'pulat@visabor.uz'],
                [
                    'name'      => 'Pulat Khaliulin',
                    'role'      => 'superadmin',
                    'password'  => Hash::make($password),
                    'is_active' => true,
                    'agency_id' => null,
                ]
            );
        });
    }
}
