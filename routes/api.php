<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExcelController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('token', [App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.login');

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('users/change-password', [App\Http\Controllers\Api\AuthController::class, 'updatePassword']);
    Route::get('logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::apiResources([
    ]);
});

Route::apiResources([
    // 'users' => App\Http\Controllers\UserController::class,
]);

Route::get('users/roles', [App\Http\Controllers\UserController::class, 'roles']);
Route::get('usuarios', [App\Http\Controllers\UserController::class, 'index']);
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('users/habilitar/{id}', [App\Http\Controllers\UserController::class, 'habilitar']);
    Route::get('users/distribuidores', [App\Http\Controllers\UserController::class, 'distribuidores']);
    Route::get('users/vendedores', [App\Http\Controllers\UserController::class, 'vendedores']);

    Route::get('unidades/habilitar/{id}', [App\Http\Controllers\UnidadController::class, 'habilitar']);
    Route::get('unidades/habilitados', [App\Http\Controllers\UnidadController::class, 'habilitados']);
    Route::get('categorias/habilitar/{id}', [App\Http\Controllers\CategoriaController::class, 'habilitar']);
    Route::get('categorias/habilitados', [App\Http\Controllers\CategoriaController::class, 'habilitados']);
    Route::get('proveedores/habilitar/{id}', [App\Http\Controllers\ProveedorController::class, 'habilitar']);
    Route::get('proveedores/habilitados', [App\Http\Controllers\ProveedorController::class, 'habilitados']);
    Route::get('suministros/habilitar/{id}', [App\Http\Controllers\SuministroController::class, 'habilitar']);
    Route::get('suministros/habilitados', [App\Http\Controllers\SuministroController::class, 'habilitados']);
    Route::get('sucursales/habilitar/{id}', [App\Http\Controllers\SucursalController::class, 'habilitar']);
    Route::get('sucursales/habilitados', [App\Http\Controllers\SucursalController::class, 'habilitados']);
    Route::get('tipopagos/habilitar/{id}', [App\Http\Controllers\TipoPagoController::class, 'habilitar']);
    Route::get('tipopagos/habilitados', [App\Http\Controllers\TipoPagoController::class, 'habilitados']);
    Route::get('ingresos/pdf/{id}', [App\Http\Controllers\ReporteController::class, 'ingresoPdf']);
    Route::get('pedidos/pdf/{id}', [App\Http\Controllers\ReporteController::class, 'pedidoPdf']);
    Route::get('agrupados/pdf/{id}', [App\Http\Controllers\ReporteController::class, 'agrupadoPdf']);
    Route::get('agrupados/despachar/{id}', [App\Http\Controllers\AgrupadoController::class, 'despachar']);
    Route::delete('agrupados/quitar/{id}', [App\Http\Controllers\AgrupadoController::class, 'quitar']);
    Route::get('agrupados/entrega/{id}', [App\Http\Controllers\PagoController::class, 'entrega']);
    Route::get('agrupados/devolver/{id}', [App\Http\Controllers\AgrupadoController::class, 'devolver']);
    Route::delete('ingresos/detalles/{id}', [App\Http\Controllers\IngresoController::class, 'eliminardetalle']);
    Route::delete('pedidos/detalles/{id}', [App\Http\Controllers\PedidoController::class, 'eliminardetalle']);
    Route::get('movilidades/habilitar/{id}', [App\Http\Controllers\MovilidadController::class, 'habilitar']);
    Route::get('movilidades/habilitados', [App\Http\Controllers\MovilidadController::class, 'habilitados']);
    Route::post('users/movilidades', [App\Http\Controllers\MovilidadController::class, 'asignar']);
    
    Route::apiResources([
        'sucursales' => App\Http\Controllers\SucursalController::class,
        'users' => App\Http\Controllers\UserController::class,
        'unidades' => App\Http\Controllers\UnidadController::class,
        'categorias' => App\Http\Controllers\CategoriaController::class,
        'proveedores' => App\Http\Controllers\ProveedorController::class,
        'suministros' => App\Http\Controllers\SuministroController::class,
        'ingresos' => App\Http\Controllers\IngresoController::class,
        'clientes' => App\Http\Controllers\ClienteController::class,
        'tipopagos' => App\Http\Controllers\TipoPagoController::class,
        'pedidos' => App\Http\Controllers\PedidoController::class,
        'movilidades' => App\Http\Controllers\MovilidadController::class,
        'agrupados' => App\Http\Controllers\AgrupadoController::class,
        'pagos' => App\Http\Controllers\PagoController::class,
    ]);
    Route::get('reportes/stock', [App\Http\Controllers\ReporteController::class, 'stock']);
    Route::get('reportes/stock-pdf', [App\Http\Controllers\ReporteController::class, 'stockPdf']);
    Route::get('reportes/detalle/{id}', [App\Http\Controllers\ReporteController::class, 'ventaProductoPdf']);
    Route::get('reportes/utilidad', [App\Http\Controllers\ReporteController::class, 'utilidadProductos']);
    Route::get('reportes/utilidad-pdf', [App\Http\Controllers\ReporteController::class, 'utilidadProductosPdf']);
    Route::get('reportes/distribucion', [App\Http\Controllers\ReporteController::class, 'distribucion']);
    Route::get('reportes/distribucion-pdf', [App\Http\Controllers\ReporteController::class, 'distribucionPdf']);
});
Route::get('reportes/creditos', [App\Http\Controllers\ReporteController::class, 'creditos']);
Route::get('reportes/creditos-pdf', [App\Http\Controllers\ReporteController::class, 'creditosPdf']);
Route::get('reportes/ventas', [App\Http\Controllers\ReporteController::class, 'ventas']);
Route::get('reportes/ventas-pdf', [App\Http\Controllers\ReporteController::class, 'ventasPdf']);
Route::get('reportes/ventaproductos', [App\Http\Controllers\ReporteController::class, 'ventaproductos']);
Route::get('reportes/ventaproductos-pdf', [App\Http\Controllers\ReporteController::class, 'ventaproductosPdf']);
Route::get('reportes/gestiones', [App\Http\Controllers\ReporteController::class, 'gestiones']);
Route::get('prueba', [App\Http\Controllers\PagoController::class, 'prueba']);

// Análisis predictivo: conserva la autenticación existente, sin permisos nuevos.
Route::middleware('auth:api')->prefix('analisis')->group(function () {
    Route::get('reporte', [App\Http\Controllers\AnalisisPredictivoController::class, 'report']);
    Route::post('entrenar', [App\Http\Controllers\AnalisisPredictivoController::class, 'train']);
});

Route::middleware('auth:api')->prefix('analisis/clientes')->group(function () {
    Route::get('{cliente}', [App\Http\Controllers\PedidoPredictivoController::class, 'detail'])->whereNumber('cliente');
    Route::post('{cliente}/pedidos', [App\Http\Controllers\PedidoPredictivoController::class, 'createSuggested'])->whereNumber('cliente');
    Route::put('{cliente}/pedidos/{pedido}', [App\Http\Controllers\PedidoPredictivoController::class, 'updateSuggested'])->whereNumber('cliente')->whereNumber('pedido');
});
