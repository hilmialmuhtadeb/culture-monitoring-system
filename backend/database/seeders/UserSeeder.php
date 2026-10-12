<?php

namespace Database\Seeders;

use App\Models\Divisi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Divisi
        |--------------------------------------------------------------------------
        */

        $divisi = Divisi::first();

        if (!$divisi) {
            $divisi = Divisi::create([
                'nama_divisi' => 'Human Capital',
                'divisi_alias' => 'HC',
                'status' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Admin
        |--------------------------------------------------------------------------
        */

        $admin = User::firstOrCreate(
            ['email' => 'admin@culture.test'],
            [
                'name' => 'Admin Culture',
                'password' => Hash::make('password'),
                'id_divisi' => $divisi->id,
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $admin->syncRoles(['Admin']);
        }

        /*
        |--------------------------------------------------------------------------
        | Tim Budaya
        |--------------------------------------------------------------------------
        */

        $timBudaya = User::firstOrCreate(
            ['email' => 'timbudaya@culture.test'],
            [
                'name' => 'Tim Budaya',
                'password' => Hash::make('password'),
                'id_divisi' => $divisi->id,
            ]
        );

        if ($timBudaya->wasRecentlyCreated) {
            $timBudaya->syncRoles(['Tim Budaya']);
        }

        /*
        |--------------------------------------------------------------------------
        | CA
        |--------------------------------------------------------------------------
        */

        $ca = User::firstOrCreate(
            ['email' => 'ca@culture.test'],
            [
                'name' => 'Change Agent',
                'password' => Hash::make('password'),
                'id_divisi' => $divisi->id,
            ]
        );

        if ($ca->wasRecentlyCreated) {
            $ca->syncRoles(['CA']);
        }
    }
}