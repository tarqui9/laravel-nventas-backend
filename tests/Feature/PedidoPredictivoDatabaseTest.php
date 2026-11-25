<?php

namespace Tests\Feature;

use App\Services\PedidoPredictivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** Prueba opcional contra MySQL: toda escritura se revierte al terminar. */
class PedidoPredictivoDatabaseTest extends TestCase
{
    private bool $opened = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('PREDICTIVE_DB_TEST') !== '1') {
            $this->markTestSkipped('Ejecutar con PREDICTIVE_DB_TEST=1 para comprobar el guardado real en MySQL.');
        }
        DB::beginTransaction();
        $this->opened = true;
    }

    protected function tearDown(): void
    {
        if ($this->opened) {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        parent::tearDown();
    }

    private function fixture(): array
    {
        $service = app(PedidoPredictivo::class);
        $branch = DB::table('sucursals')->where('nombre', 'Sucursal DEMO 2025')->value('id');
        $client = DB::table('clientes')->where('sucursal_id', $branch)->first();
        $this->assertNotNull($client, 'Faltan datos de demostración.');
        $payment = DB::table('cliente_tipopagos')->where('cliente_id', $client->id)->value('tipo_pago_id');
        $ids = DB::table('pedido_detalles as d')->join('pedidos as p', 'p.id', '=', 'd.pedido_id')
            ->where('p.cliente_id', $client->id)->where('p.estado', 'ENTREGADO')->distinct()->pluck('d.suministro_id')->all();
        $available = $service->availability($branch, $ids);
        $pid = collect($available)->filter(fn ($qty) => $qty >= 3)->keys()->first();
        $this->assertNotNull($pid, 'Falta un producto con tres unidades disponibles.');
        $body = ['clave' => (string) Str::uuid(), 'referencia' => '2025-12-31',
                 'tipo_pago_id' => $payment, 'observacion' => 'Prueba con rollback',
                 'detalle' => [['suministro_id' => $pid, 'cantidad' => 1]]];
        return [$service, $client, $pid, $available, $body];
    }

    public function test_real_save_edit_stock_and_idempotency_without_delivery(): void
    {
        [$service, $client, $pid, $available, $body] = $this->fixture();
        $user = DB::table('users')->value('id');
        $stock = DB::table('stocks')->where('sucursal_id', $client->sucursal_id)->where('suministro_id', $pid)->value('cantidad');
        $created = $service->save($client->id, $body, $user);
        $again = $service->save($client->id, $body, $user);
        $this->assertEquals($created['id'], $again['id']);
        $this->assertTrue($again['reutilizado']);
        $this->assertEquals('PEDIDO', DB::table('pedidos')->where('id', $created['id'])->value('estado'));
        $this->assertEquals($available[$pid]-1, $service->availability($client->sucursal_id, [$pid])[$pid]);
        $body['detalle'][0]['cantidad'] = 2;
        $service->save($client->id, $body, $user, $created['id']);
        $this->assertEquals(2, DB::table('pedido_detalles')->where('pedido_id', $created['id'])->value('cantidad'));
        $this->assertEquals($available[$pid]-2, $service->availability($client->sucursal_id, [$pid])[$pid]);
        $this->assertEquals($stock, DB::table('stocks')->where('sucursal_id', $client->sucursal_id)->where('suministro_id', $pid)->value('cantidad'));
        $this->assertEquals(0, DB::table('pagos')->where('pedido_id', $created['id'])->count());
        $this->assertEquals(0, DB::table('pedido_salidas')->where('pedido_id', $created['id'])->count());
    }

    public function test_insufficient_stock_creates_no_partial_order(): void
    {
        [$service, $client, $pid, $available, $body] = $this->fixture();
        $before = DB::table('pedidos')->count();
        $body['detalle'][0]['cantidad'] = $available[$pid]+1;
        try {
            $service->save($client->id, $body, DB::table('users')->value('id'));
            $this->fail('Se aceptó una cantidad superior al stock.');
        } catch (ValidationException $error) {
            $this->assertEquals($before, DB::table('pedidos')->count());
        }
    }
}
