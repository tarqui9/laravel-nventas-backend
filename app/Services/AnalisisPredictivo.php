<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\Process\Process;

class AnalisisPredictivo
{
    /** Lee únicamente la sucursal simulada, sin mezclar ventas reales. */
    public function dataset(): array
    {
        return DB::transaction(function () {
            $branch = DB::table('sucursals')->where('nombre', 'Sucursal DEMO 2025')->first();
            if (!$branch) {
                throw new RuntimeException('Primero carga los datos: php artisan db:seed --class=Analisis2025Seeder');
            }
            $orders = DB::table('pedidos')->where('sucursal_id', $branch->id)
                ->where('estado', 'ENTREGADO')->orderBy('fecha')->get();
            $details = DB::table('pedido_detalles')->whereIn('pedido_id', $orders->pluck('id'))->get();
            $products = DB::table('suministros')->whereIn('id', $details->pluck('suministro_id')->unique())->get();
            $clients = DB::table('clientes')->where('sucursal_id', $branch->id)->get();
            $stocks = DB::table('stocks')->where('sucursal_id', $branch->id)->get();
            if ($orders->count() < 100) {
                throw new RuntimeException('Faltan ventas de demostración para entrenar.');
            }
            return [
                'pedidos' => $orders->map(fn ($row) => (array) $row)->all(),
                'pedido_detalles' => $details->map(fn ($row) => (array) $row)->all(),
                'suministros' => $products->map(fn ($row) => (array) $row)->all(),
                'clientes' => $clients->map(fn ($row) => (array) $row)->all(),
                'stocks' => $stocks->map(fn ($row) => (array) $row)->all(),
            ];
        });
    }

    public function train(): array
    {
        $health = Http::connectTimeout(3)->timeout(5)->get(config('predictivo.url').'/health');
        if (!$health->successful()) {
            throw new RuntimeException('No se pudo comprobar el servicio de Python. Reinicia ./levantar.sh.');
        }
        // Un servicio que quedó abierto no recarga el código. Usa el motor actual
        // para no volver a guardar un informe antiguo sin productos por cliente.
        if ((int) $health->json('version_informe', 0) < 3) {
            return $this->trainLocally();
        }
        $tables = $this->dataset();
        $response = Http::connectTimeout(3)->timeout(180)
            ->post(config('predictivo.url').'/train', ['tables' => $tables, 'source' => 'mysql_demo']);
        if (!$response->successful()) {
            throw new RuntimeException($response->json('detail') ?: 'No se pudo completar el entrenamiento.');
        }
        return $response->json();
    }

    public function trainLocally(): array
    {
        $python = base_path('../ml/.venv/bin/python');
        if (!is_executable($python)) {
            throw new RuntimeException('Falta el entorno Python en ml/.venv.');
        }
        $process = new Process([$python, 'engine.py', '--stdin'], base_path('../ml'));
        $process->setInput(json_encode(['tables' => $this->dataset(), 'source' => 'mysql_demo'], JSON_THROW_ON_ERROR));
        $process->setTimeout(180);
        $process->run();
        if (!$process->isSuccessful()) {
            \Illuminate\Support\Facades\Log::error('Error del motor predictivo local', ['detalle' => $process->getErrorOutput()]);
            throw new RuntimeException('No se pudo ejecutar el entrenamiento actualizado. Revisa las dependencias de ml y el registro de Laravel.');
        }
        $report = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        if (($report['version_informe'] ?? 0) < 3) {
            throw new RuntimeException('El motor no generó el informe actualizado de productos por cliente.');
        }
        return $report;
    }
}
