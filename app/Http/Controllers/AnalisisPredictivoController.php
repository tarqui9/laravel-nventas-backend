<?php

namespace App\Http\Controllers;

use App\Services\AnalisisPredictivo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AnalisisPredictivoController extends Controller
{
    public function report()
    {
        try {
            $response = Http::connectTimeout(3)->timeout(10)->get(config('predictivo.url').'/report');
            if ($response->status() === 404) {
                return response()->json(['message' => 'Todavía no hay entrenamiento. Abre Entrenamiento y pulsa Entrenar análisis.'], 404);
            }
            if (!$response->successful()) {
                return response()->json(['message' => 'El servicio predictivo no pudo entregar el resultado.'], 502);
            }
            return response()->json($response->json());
        } catch (ConnectionException $error) {
            return response()->json(['message' => 'El servicio de Python está apagado. Inícialo con ./levantar.sh.'], 503);
        }
    }

    public function train(AnalisisPredictivo $analysis)
    {
        // PHP también debe permitir el tiempo de entrenamiento del servicio local.
        @set_time_limit(210);
        try {
            return response()->json($analysis->train());
        } catch (ConnectionException $error) {
            return response()->json(['message' => 'El servicio de Python no respondió. Comprueba que esté iniciado con ./levantar.sh.'], 503);
        } catch (\PDOException $error) {
            Log::error('No se pudo leer MySQL para el análisis', ['exception' => $error]);
            return response()->json(['message' => 'No se pudo conectar con MySQL. Comprueba que esté encendido y que Laravel tenga la conexión correcta.'], 503);
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            Log::error('Error de entrenamiento predictivo', ['exception' => $error]);
            return response()->json(['message' => 'No se pudo leer la base de datos o entrenar. Revisa la conexión MySQL y el registro de Laravel.'], 500);
        }
    }
}
