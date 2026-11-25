<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Agrega la demostración 2025 sin modificar registros existentes. */
class Analisis2025Seeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('../datos/simulados_2025/datos.json');
        if (!is_file($file)) {
            throw new RuntimeException('Primero ejecuta python datos/simulados_2025/generar.py desde la carpeta principal.');
        }
        $data = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $references = [
            'sucursal_id' => 'sucursals', 'categoria_id' => 'categorias',
            'unidad_id' => 'unidads', 'tipo_pago_id' => 'tipo_pagos',
            'proveedor_id' => 'proveedors', 'suministro_id' => 'suministros',
            'cliente_id' => 'clientes', 'ingreso_id' => 'ingresos',
            'pedido_id' => 'pedidos', 'ingreso_detalle_id' => 'ingreso_detalles',
        ];
        DB::transaction(function () use ($data, $references) {
            // Serializa este cargador para impedir dos importaciones simultáneas.
            $user = DB::table('users')->orderBy('id')->lockForUpdate()->first();
            if (!$user) {
                throw new RuntimeException('Se necesita un usuario existente. Este cargador no crea ni cambia accesos.');
            }
            if (DB::table('sucursals')->where('nombre', 'Sucursal DEMO 2025')->exists()) {
                throw new RuntimeException('La demostración 2025 ya está cargada; no se duplicaron registros.');
            }
            $ids = [];
            foreach ($data as $table => $rows) {
                foreach ($rows as $row) {
                    $sourceId = $row['id'];
                    unset($row['id']);
                    foreach ($references as $column => $parent) {
                        if (isset($row[$column])) {
                            if (!isset($ids[$parent][$row[$column]])) {
                                throw new RuntimeException("Referencia inválida en {$table}.{$column}");
                            }
                            $row[$column] = $ids[$parent][$row[$column]];
                        }
                    }
                    if (in_array($table, ['clientes', 'ingresos', 'pedidos', 'pagos'], true)) {
                        $row['user_id'] = $user->id;
                    }
                    $timestamp = $row['fecha_registro'] ?? $row['fecha'] ?? '2025-01-01 00:00:00';
                    if (strlen($timestamp) === 10) {
                        $timestamp .= ' 00:00:00';
                    }
                    $row['created_at'] = $timestamp;
                    $row['updated_at'] = $timestamp;
                    $ids[$table][$sourceId] = DB::table($table)->insertGetId($row);
                }
            }
        });
        $this->command?->info('Demostración 2025 cargada: 20 productos, 40 clientes y 933 pedidos.');
    }
}
