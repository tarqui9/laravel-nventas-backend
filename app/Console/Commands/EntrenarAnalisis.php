<?php

namespace App\Console\Commands;

use App\Services\AnalisisPredictivo;
use Illuminate\Console\Command;
use Throwable;

class EntrenarAnalisis extends Command
{
    protected $signature = 'analisis:entrenar {--local : Ejecuta el motor actualizado directamente, leyendo MySQL}';
    protected $description = 'Entrena y evalúa el análisis con los datos simulados cargados en MySQL';

    public function handle(AnalisisPredictivo $analysis): int
    {
        $this->info('Leyendo MySQL y entrenando modelos. Puede tardar un momento...');
        try {
            $report = $this->option('local') ? $analysis->trainLocally() : $analysis->train();
            $this->info('Entrenamiento terminado. Pedidos: '.$report['resumen']['pedidos']);
            $this->line('Demanda: '.$report['demanda']['metodo_utilizado']);
            $this->line('MAE modelo: '.$report['demanda']['mae_modelo'].'; referencia: '.$report['demanda']['mae_referencia']);
            $this->line('F1 recompra: '.$report['recompra']['f1']);
            if (isset($report['tiempo_recompra']) && $report['tiempo_recompra']['disponible']) {
                $this->line('Error del plazo de recompra: '.$report['tiempo_recompra']['mae_dias'].' días; referencia: '.$report['tiempo_recompra']['mae_referencia_dias'].' días.');
                $this->line('Fechas aproximadas disponibles en Clientes y recompra.');
            } elseif (!isset($report['tiempo_recompra'])) {
                $this->warn('Este informe todavía no incluye plazos de recompra. Si actualizaste el programa, reinicia Python y vuelve a entrenar.');
            } else {
                $this->warn('No hay suficientes ejemplos para estimar y evaluar el plazo de recompra.');
            }
            if (!empty($report['productos_cliente']['disponible'])) {
                $withPercentages = collect($report['clientes'])->filter(fn ($c) => collect($c['productos'] ?? [])->contains(fn ($p) => isset($p['probabilidad_producto'])))->count();
                $this->info('Predicciones por producto disponibles para '.$withPercentages.' clientes.');
            } else {
                $this->warn('No se generaron porcentajes por producto: revisa las muestras de entrenamiento.');
            }
            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error('No se pudo entrenar. Comprueba MySQL, los datos DEMO y el servicio Python en el puerto 8001.');
            report($error);
            return self::FAILURE;
        }
    }
}
