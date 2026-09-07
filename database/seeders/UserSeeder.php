<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $user = null;

            User::withoutEvents(function () use (&$user) {
                $user = User::updateOrCreate(
                    [
                        'email' => 'admin@inventory.com',
                    ],
                    [
                        'name' => 'Super Admin',
                        'password' => bcrypt('password'),
                        'email_verified_at' => now(),
                    ]
                );
            });
            DB::commit();
        } catch (Throwable $th) {
            DB::rollBack();
            echo $th->getMessage() . PHP_EOL;
        }
    }
}
