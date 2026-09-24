<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PruebaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\Categoria::create([
        //     'nombre' => 'Cat nuevo',
        //     'rango_min' => 50,
        //     'rango_max' => 80,
        //     'habilitado' => true,
        // ]);
        
        $categorias = [
            'Limpieza',
            'Escritorio',
        ];
        foreach ($categorias as $value) {
            // \App\Models\Categoria::create([
            //     'nombre' => $value,
            //     'habilitado' => true,
            // ]);
        }
        $categorias = [
            'Sucursal Principal',
            // 'Sucursal Norte',
        ];
        foreach ($categorias as $value) {
            \App\Models\Sucursal::create([
                'nombre' => $value,
                'habilitado' => true,
            ]);
        }
        $categorias = [
            'credito',
            'contra entrega',
            'pre pago',
            'mayoristas',
        ];
        foreach ($categorias as $value) {
            \App\Models\TipoPago::create([
                'nombre' => $value,
                'descuento' => rand(0,25),
                'habilitado' => true,
            ]);
        }
        \App\Models\Unidad::create([
            'nombre' => 'Unidad',
            'sigla' => 'UN',
            'habilitado' => true,
        ]);
        \App\Models\Unidad::create([
            'nombre' => 'Paquete',
            'sigla' => 'PQTE',
            'habilitado' => true,
        ]);
        \App\Models\Unidad::create([
            'nombre' => 'Caja',
            'sigla' => 'CJA',
            'habilitado' => true,
        ]);
        \App\Models\Proveedor::create([
            'nombre' => 'Juan Dias',
            'razon_social' => 'LIMPIEZA Dias S.A.',
            'ciudad' => 'CBBA',
            'pais' => 'Bolivia',
            'direccion' => 'Av. Blanco Galindo Km 4.5',
            'telefono' => '4123565',
            'habilitado' => true,
        ]);
        
        // \App\Models\Estudiante::create([
        //     'nombres' => 'Juan',
        //     'apellidos' => 'Pérez',
        //     'fecha_nacimiento' => '1990-02-15',
        //     'genero' => 'M',
        //     'cinturon' => 'NEGRO',
        //     'peso' => 75,
        //     'habilitado' => true,
        //     'categoria_id' => 1,
        // ]);
        
        $cliente = \App\Models\Cliente::create([
            'contacto' => 'Fernando Robles',
            'razon_social' => 'ROBLES S.A.',
            'nit_ci' => 'CBBA',
            'direccion' => 'Bolivia',
            'celular' => 'Av. Blanco Galindo Km 4.5',
            'telefono' => '4123565',
            'sucursal_id' => true,
            'user_id' => true,
        ]);

        $cliente->tipopagos()->sync([1,2]);
    }
}
