<?php

namespace App\Http\Controllers;

use App\Services\PedidoPredictivo;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use RuntimeException;
use Throwable;

class PedidoPredictivoController extends Controller
{
    public function detail(int $cliente, PedidoPredictivo $service)
    {
        return $this->respond(fn () => $service->detail($cliente));
    }

    public function createSuggested(Request $request, int $cliente, PedidoPredictivo $service)
    {
        $input = $this->validateOrder($request);
        return $this->respond(fn () => $service->save($cliente, $input, $request->user()->id), 201);
    }

    public function updateSuggested(Request $request, int $cliente, int $pedido, PedidoPredictivo $service)
    {
        $input = $this->validateOrder($request);
        return $this->respond(fn () => $service->save($cliente, $input, $request->user()->id, $pedido));
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'clave' => ['required', 'uuid'], 'referencia' => ['required', 'date_format:Y-m-d'],
            'tipo_pago_id' => ['required', 'integer', 'min:1'],
            'observacion' => ['nullable', 'string', 'max:1000'],
            'detalle' => ['required', 'array', 'min:1', 'max:100'],
            'detalle.*.suministro_id' => ['required', 'integer', 'min:1', 'distinct'],
            'detalle.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);
    }

    private function respond(callable $action, int $status = 200)
    {
        try {
            return response()->json($action(), $status);
        } catch (ValidationException | HttpExceptionInterface $error) {
            throw $error;
        } catch (ConnectionException $error) {
            return response()->json(['message' => 'Python no responde. Inicia los servicios y vuelve a intentar.'], 503);
        } catch (\PDOException $error) {
            Log::error('Error MySQL en pedido predictivo', ['exception' => $error]);
            return response()->json(['message' => 'No se pudo leer o guardar en MySQL. Comprueba la conexión.'], 503);
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 422);
        } catch (Throwable $error) {
            Log::error('Error de pedido predictivo', ['exception' => $error]);
            return response()->json(['message' => 'No se pudo completar el pedido. Revisa el registro de Laravel.'], 500);
        }
    }
}
