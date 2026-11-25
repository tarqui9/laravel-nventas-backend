<?php

namespace App\Console\Commands;

use App\Services\AnalisisPredictivo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class EstadoAnalisis extends Command
{
    protected $signature = 'analisis:estado';
    protected $description = 'Comprueba datos de demostración en MySQL y disponibilidad de Python';

    public function handle(AnalisisPredictivo $analysis): int
    {
        try {
            $tables = $analysis->dataset();
            $this->info('MySQL conectado. Datos de demostración:');
            foreach ($tables as $table => $rows) {
                $this->line($table.': '.count($rows));
            }
        } catch (Throwable $error) {
            $this->error('No se pudieron leer los datos. Revisa MySQL y ejecuta el cargador Analisis2025Seeder si aún no terminó.');
            report($error);
            return self::FAILURE;
        }
        try {
            Http::connectTimeout(3)->timeout(5)->get(config('predictivo.url').'/health')->throw();
            $this->info('Python disponible en '.config('predictivo.url'));
        } catch (Throwable $error) {
            $this->warn('Los datos están disponibles, pero Python no responde. Ejecuta ./levantar.sh desde la raíz.');
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
