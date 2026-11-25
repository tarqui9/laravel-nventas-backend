<?php

namespace Tests\Feature;

use App\Services\AnalisisPredictivo;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;
use Mockery;

class AnalisisPredictivoTest extends TestCase
{
    public function test_report_returns_python_result(): void
    {
        $this->withoutMiddleware();
        Http::fake(['*/report' => Http::response(['fuente' => 'mysql_demo', 'resumen' => ['pedidos' => 933]])]);
        $this->getJson('/api/analisis/reporte')->assertOk()->assertJsonPath('resumen.pedidos', 933);
    }

    public function test_missing_report_has_a_useful_message(): void
    {
        $this->withoutMiddleware();
        Http::fake(['*/report' => Http::response(['detail' => 'missing'], 404)]);
        $this->getJson('/api/analisis/reporte')->assertNotFound()->assertJsonStructure(['message']);
    }

    public function test_training_uses_the_service(): void
    {
        $this->withoutMiddleware();
        $this->mock(AnalisisPredictivo::class, function ($mock) {
            $mock->shouldReceive('train')->once()->andReturn(['fuente' => 'mysql_demo']);
        });
        $this->postJson('/api/analisis/entrenar')->assertOk()->assertJsonPath('fuente', 'mysql_demo');
    }

    public function test_missing_dataset_is_a_readable_validation_error(): void
    {
        $this->withoutMiddleware();
        $this->mock(AnalisisPredictivo::class, function ($mock) {
            $mock->shouldReceive('train')->once()->andThrow(new RuntimeException('Primero carga los datos de demostración.'));
        });
        $this->postJson('/api/analisis/entrenar')->assertStatus(422)->assertJsonPath('message', 'Primero carga los datos de demostración.');
    }

    public function test_old_python_service_uses_the_current_local_engine(): void
    {
        Http::fake(['*/health' => Http::response(['status' => 'ok'])]);
        $analysis = Mockery::mock(AnalisisPredictivo::class)->makePartial();
        $analysis->shouldReceive('trainLocally')->once()->andReturn(['version_informe' => 3]);
        $analysis->shouldNotReceive('dataset');
        $this->assertEquals(3, $analysis->train()['version_informe']);
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/train'));
    }

    public function test_current_python_service_trains_through_fastapi(): void
    {
        Http::fake([
            '*/health' => Http::response(['status' => 'ok', 'version_informe' => 3]),
            '*/train' => Http::response(['version_informe' => 3, 'productos_cliente' => ['disponible' => true]]),
        ]);
        $analysis = Mockery::mock(AnalisisPredictivo::class)->makePartial();
        $analysis->shouldReceive('dataset')->once()->andReturn(['pedidos' => []]);
        $analysis->shouldNotReceive('trainLocally');
        $this->assertTrue($analysis->train()['productos_cliente']['disponible']);
    }
}
