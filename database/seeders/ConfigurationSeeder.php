<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'administrador',
            'vendedor',
            'distribuidor',
            'almacen',
            // 'carga',
        ];
        foreach ($roles as $key => $value) {
            \App\Models\Role::create([
                'name' => $value,
                'display_name' => $value,
            ]);
        }
        \App\Models\Role::create([
            'name' => 'almacen',
            'display_name' => 'Encargado de Almacen',
        ]);

        $faker = Faker::create();
        $nombre = $faker->name();
        $apellido = $faker->lastname();
        // $nombre = 'Mauricio';
        // $apellido = 'Roldan Herbas';


        \App\Models\User::factory()->create([
            'nombres' => $nombre,
            'apellidos' => $apellido,
            'nombre_completo' => $nombre . ' ' . $apellido,
            'username' => 'admin',
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'habilitado' => true,
            'password' => bcrypt('admin24'), // password
        ]);
        \App\Models\User::first()->roles()->sync([1]);
    }
}
