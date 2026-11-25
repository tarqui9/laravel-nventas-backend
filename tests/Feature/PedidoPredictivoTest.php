<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PedidoPredictivo;
use Tests\TestCase;

class PedidoPredictivoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $user = new User;
        $user->id = 7;
        $this->actingAs($user);
    }

    private function payload(): array
    {
        return ['clave' => 'd04ddeac-6faf-4c26-a9ab-745a990466a2', 'referencia' => '2025-12-31',
                'tipo_pago_id' => 1, 'detalle' => [['suministro_id' => 3, 'cantidad' => 2]]];
    }

    public function test_client_detail_uses_the_selected_client(): void
    {
        $this->mock(PedidoPredictivo::class, function ($mock) {
            $mock->shouldReceive('detail')->with(12)->once()->andReturn(['cliente' => ['id' => 12], 'productos' => []]);
        });
        $this->getJson('/api/analisis/clientes/12')->assertOk()->assertJsonPath('cliente.id', 12);
    }

    public function test_selected_lines_are_saved_as_a_real_order_request(): void
    {
        $this->mock(PedidoPredictivo::class, function ($mock) {
            $mock->shouldReceive('save')->with(12, $this->payload(), 7)->once()->andReturn(['id' => 1001, 'total' => 27.8]);
        });
        $this->postJson('/api/analisis/clientes/12/pedidos', $this->payload())->assertCreated()->assertJsonPath('id', 1001);
    }

    public function test_duplicate_products_are_rejected_before_saving(): void
    {
        $this->mock(PedidoPredictivo::class, fn ($mock) => $mock->shouldNotReceive('save'));
        $body = $this->payload();
        $body['detalle'][] = $body['detalle'][0];
        $this->postJson('/api/analisis/clientes/12/pedidos', $body)->assertUnprocessable()->assertJsonValidationErrors(['detalle.0.suministro_id']);
    }

    public function test_empty_fractional_and_negative_quantities_are_rejected(): void
    {
        $this->mock(PedidoPredictivo::class, fn ($mock) => $mock->shouldNotReceive('save'));
        foreach ([0, -1, 1.5] as $quantity) {
            $body = $this->payload();
            $body['detalle'][0]['cantidad'] = $quantity;
            $this->postJson('/api/analisis/clientes/12/pedidos', $body)->assertUnprocessable();
        }
        $body['detalle'] = [];
        $this->postJson('/api/analisis/clientes/12/pedidos', $body)->assertUnprocessable();
    }

    public function test_existing_generated_order_can_be_edited(): void
    {
        $this->mock(PedidoPredictivo::class, function ($mock) {
            $mock->shouldReceive('save')->with(12, $this->payload(), 7, 1001)->once()->andReturn(['id' => 1001, 'total' => 27.8]);
        });
        $this->putJson('/api/analisis/clientes/12/pedidos/1001', $this->payload())->assertOk()->assertJsonPath('id', 1001);
    }
}
