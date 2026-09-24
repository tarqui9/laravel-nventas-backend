<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use App\Models\Stock;
use App\Models\Pedido;
use App\Models\Suministro;
use Illuminate\Http\Request;
use App\Models\PedidoDetalle;
use App\Models\IngresoDetalle;
use App\Models\PedidoAgrupado;
use App\Models\ProductoUnidad;
use App\Models\SolicitudDetalle;
use Illuminate\Database\Eloquent\Collection;

class SuministroController extends Controller
{
    public function index(Request $request) {
        $categoria_id = $request->get('categoria_id');
        $data = Suministro::scategoria($categoria_id)
        ->latest()
        ->with(['categoria'])->get();
        return response()->json($data, 200);
    }
    public function habilitados(Request $request) {
        $term = $request->get('term');
        $sucursal_id = 1;
        $data = Suministro::sterm($term,'')
        ->with(['categoria'])->habilitado()->get();
        $pedidosDespachados = PedidoAgrupado::from('pedido_agrupados as pa')
        ->join('agrupados as g','g.id','pa.grupo_id')
        ->where('g.estado','DESPACHADO')
        ->pluck('pa.pedido_id');
        foreach ($data as $key => $value) {
            $query = Stock::where('suministro_id',$value->id)
            ->where('sucursal_id',$sucursal_id)->first();
            $sub_query = Pedido::from('pedidos as p')
            ->join('pedido_detalles as dt','dt.pedido_id','p.id')
            // ->select('p.*','dt.suministro_id','dt.cantidad')
            ->whereNotIn('p.id', $pedidosDespachados)
            ->where('p.estado','PEDIDO')
            ->where('dt.suministro_id',$value->id)
            ->sum('dt.cantidad');
            if ($query) {
                $value->stock = $query->cantidad - $sub_query;
            }
        }
        return response()->json($data, 200);
    }
    public function show($id) {
        $data = Suministro::with(['categoria'])->find($id);
        return response()->json($data, 200);
    }
    public function store(Request $request) {
        $val = Suministro::where('nombre',$request->nombre)->first();
        if ($val) {
            return response()->json('El nombre de producto ya fue resgistrado.', 409);
        }
        if ($request->codigo) {
            $verificarCodigo = Suministro::where('codigo',$request->codigo)->first();
            if ($verificarCodigo) {
                return response()->json('El código ya fue resgistrado. (' . $verificarCodigo->nombre . ')', 409);
            }
        }
        $name = null;
        $dir_folder = 'images/suministros';
        if($request->hasFile('file')) {
            if (!is_dir(public_path().'/'.$dir_folder)) {
                $crearDir = mkdir(public_path().'/'.$dir_folder, 0777, true);
            }
            $file = $request->file('file');
            //concatena la hora y el nombre del archivo
            $name_file = time().$file->getClientOriginalName();
            $name = $dir_folder.'/'.$name_file;
            $file->move(public_path().'/'.$dir_folder,  $name_file);
        }
        $newData = new Suministro;
        if ($name) {
            $newData->foto = $name;
        }
        $newData->nombre = $request->nombre;
        $newData->descripcion = $request->descripcion!='null'?$request->descripcion:null;
        $newData->codigo = $request->codigo!='null'?$request->codigo:null;
        $newData->cantidad_unidades = $request->cantidad_unidades;
        $newData->precio = $request->precio;
        $newData->stock = 0;
        $newData->estado = 'REGISTRADO';
        $newData->cantidad_total = 0;
        $newData->habilitado = true;
        $newData->categoria_id = $request->categoria_id;
        $newData->save();

        // $unidades = json_decode($request->unidades);
        // foreach ($unidades as $uni) {
        //     $newUnidad = new ProductoUnidad;
        //     $newUnidad->factor = $uni->factor;
        //     $newUnidad->precio = $uni->precio;
        //     $newUnidad->habilitado = true;
        //     $newUnidad->suministro_id = $newData->id;
        //     $newUnidad->unidad_id = $uni->unidad_id;
        //     $newUnidad->save();
        // }
        $data = [
            'success' => 'Registrado'
        ];
        return response()->json($data, 200);
    }
    public function update(Request $request, $id) {
        $val = Suministro::where('nombre',$request->nombre)
        ->where('id','!=',$id)->first();
        if ($val) {
            return response()->json('El nombre de producto ya fue resgistrado.', 409);
        }
        $newData = Suministro::find($id);
        if (!$newData) {
            return response()->json('No encontrado', 409);
        }
        if ($request->codigo) {
            $verificarCodigo = Suministro::where('codigo',$request->codigo)
            ->where('id','!=',$id)->first();
            if ($verificarCodigo) {
                return response()->json('El código ya fue resgistrado. (' . $verificarCodigo->nombre . ')', 409);
            }
        }
        $name = null;
        $dir_folder = 'images/suministros';
        if($request->hasFile('file')) {
            if (!is_dir(public_path().'/'.$dir_folder)) {
                $crearDir = mkdir(public_path().'/'.$dir_folder, 0777, true);
            }
            $file = $request->file('file');
            //concatena la hora y el nombre del archivo
            $name_file = time().$file->getClientOriginalName();
            $name = $dir_folder.'/'.$name_file;
            $file->move(public_path().'/'.$dir_folder,  $name_file);
        }
        if ($name) {
            $newData->foto = $name;
        }
        $newData->nombre = $request->nombre;
        $newData->precio = $request->precio;
        $newData->cantidad_unidades = $request->cantidad_unidades;
        $newData->descripcion = $request->descripcion!='null'?$request->descripcion:null;
        $newData->codigo = $request->codigo!='null'?$request->codigo:null;
        $newData->categoria_id = $request->categoria_id;
        $newData->save();
        
        $deleteUnidades = ProductoUnidad::where('suministro_id',$id)->get();
        foreach ($deleteUnidades as $del) {
            $del->delete();
        }
        
        // $unidades = json_decode($request->unidades);
        // foreach ($unidades as $uni) {
        //     $newUnidad = new ProductoUnidad;
        //     $newUnidad->factor = $uni->factor;
        //     $newUnidad->precio = $uni->precio;
        //     $newUnidad->habilitado = true;
        //     $newUnidad->suministro_id = $newData->id;
        //     $newUnidad->unidad_id = $uni->unidad_id;
        //     $newUnidad->save();
        // }
        $data = [
            'success' => 'Actualizado'
        ];
        return response()->json($data, 200);
    }
    public function destroy($id) {
        $query = Suministro::find($id);
        if (!$query) {
            return response()->json('No encontrado', 409);
        }
        $valid = IngresoDetalle::where('suministro_id',$id)->first();
        if ($valid) {
            return response()->json('No puede ser eliminado.', 409);
        }
        $valid = PedidoDetalle::where('suministro_id',$id)->first();
        if ($valid) {
            return response()->json('No puede ser eliminado.', 409);
        }
        $query->delete();
        
        $data = [
            'success' => 'Eliminado'
        ];
        return response()->json($data, 200);
    }
    function calcularCostoPromedioPEPS($suministroId) {
        // Obtener todas las entradas de suministro ordenadas por fecha de ingreso
        $ingresos = IngresoDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
        $ventas = VentaDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
    
        $cantidadTotal = 0;
        $costoTotal = 0;
    
        // Procesar las compras para calcular el costo total inicial
        foreach ($ingresos as $ingreso) {
            $cantidadTotal += $ingreso->cantidad;
            $costoTotal += $ingreso->cantidad * $ingreso->costo_unitario;
        }
    
        // Procesar las ventas
        foreach ($ventas as $venta) {
            $cantidadVendida = $venta->cantidad;
    
            // Ajustar el stock y el costo total según PEPS
            foreach ($ingresos as $ingreso) {
                if ($cantidadVendida <= 0) break;
    
                if ($ingreso->cantidad > 0) {
                    $cantidadDelLote = min($cantidadVendida, $ingreso->cantidad);
                    $cantidadVendida -= $cantidadDelLote;
                    $cantidadTotal -= $cantidadDelLote;
                    $costoTotal -= $cantidadDelLote * $ingreso->costo_unitario;
                    $ingreso->cantidad -= $cantidadDelLote; // Restar del lote
                }
            }
        }
    
        // Calcular el costo promedio final
        if ($cantidadTotal > 0) {
            return $costoTotal / $cantidadTotal;
        } else {
            return 0; // Evitar división por cero
        }
    }
    function calcularCostoPromedioWEBS($suministroId) {
        // Obtener todas las entradas de suministro ordenadas por fecha de ingreso
        $ingresos = IngresoDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'desc')->get();
        $ventas = VentaDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
    
        $cantidadTotal = 0;
        $costoTotal = 0;
    
        // Procesar las compras para calcular el costo total inicial
        foreach ($ingresos as $ingreso) {
            $cantidadTotal += $ingreso->cantidad;
            $costoTotal += $ingreso->cantidad * $ingreso->costo_unitario;
        }
    
        // Procesar las ventas usando WEBS
        foreach ($ventas as $venta) {
            $cantidadVendida = $venta->cantidad;
    
            // Ajustar el stock y el costo total según WEBS
            foreach ($ingresos as $ingreso) {
                if ($cantidadVendida <= 0) break;
    
                if ($ingreso->cantidad > 0) {
                    $cantidadDelLote = min($cantidadVendida, $ingreso->cantidad);
                    $cantidadVendida -= $cantidadDelLote;
                    $cantidadTotal -= $cantidadDelLote;
                    $costoTotal -= $cantidadDelLote * $ingreso->costo_unitario;
                    $ingreso->cantidad -= $cantidadDelLote; // Restar del lote
                }
            }
        }
    
        // Calcular el costo promedio final
        if ($cantidadTotal > 0) {
            return $costoTotal / $cantidadTotal;
        } else {
            return 0; // Evitar división por cero
        }
    }
    function calcularInventarioPEPS($ingresos, $ventas) {
        // Obtener todas las entradas de suministro ordenadas por fecha de ingreso (más antiguas primero)
        // $ingresos = IngresoDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
        // $ventas = VentaDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
    
        $stockRestante = [];
        $valorInventario = 0;
    
        // Inicializar el stock restante con los lotes actuales
        foreach ($ingresos as $ingreso) {
            $stockRestante[] = [
                'cantidad' => $ingreso->cantidad,
                'costo_unitario' => $ingreso->costo_unitario
            ];
        }
    
        // Procesar las ventas usando PEPS
        foreach ($ventas as $venta) {
            // $cantidadVendida = $venta->cantidad;
            $cantidadVendida = $venta->cantidad_entregado - $venta->cantidad_devuelto;
    
            // Restar del stock los lotes más antiguos primero
            foreach ($stockRestante as &$lote) {
                if ($cantidadVendida <= 0) break;
    
                if ($lote['cantidad'] > 0) {
                    $cantidadDelLote = min($cantidadVendida, $lote['cantidad']);
                    $cantidadVendida -= $cantidadDelLote;
                    $lote['cantidad'] -= $cantidadDelLote;
                }
            }
        }
    
        // Calcular el valor del inventario restante
        foreach ($stockRestante as $lote) {
            $valorInventario += $lote['cantidad'] * $lote['costo_unitario'];
        }
    
        return $valorInventario;
    }
    function calcularInventarioWEBS($ingresos, $ventas) {
        // Obtener todas las entradas de suministro ordenadas por fecha de ingreso (más recientes primero)
        // $ingresos = IngresoDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'desc')->get();
        // $ventas = VentaDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
    
        $stockRestante = [];
        $valorInventario = 0;
    
        // Inicializar el stock restante con los lotes actuales
        foreach ($ingresos as $ingreso) {
            $stockRestante[] = [
                'cantidad' => $ingreso->cantidad,
                'costo_unitario' => $ingreso->costo_unitario
            ];
        }
    
        // Procesar las ventas usando WEBS
        foreach ($ventas as $venta) {
            // $cantidadVendida = $venta->cantidad;
            $cantidadVendida = $venta->cantidad_entregado - $venta->cantidad_devuelto;
    
            // Restar del stock los lotes más recientes primero
            foreach ($stockRestante as &$lote) {
                if ($cantidadVendida <= 0) break;
    
                if ($lote['cantidad'] > 0) {
                    $cantidadDelLote = min($cantidadVendida, $lote['cantidad']);
                    $cantidadVendida -= $cantidadDelLote;
                    $lote['cantidad'] -= $cantidadDelLote;
                }
            }
        }
    
        // Calcular el valor del inventario restante
        foreach ($stockRestante as $lote) {
            $valorInventario += $lote['cantidad'] * $lote['costo_unitario'];
        }
    
        return $valorInventario;
    }
    function calcularCostoPromedio($compras, $ventas) {
        // $compras = IngresoDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
        // $ventas = VentaDetalle::where('suministro_id', $suministroId)->orderBy('created_at', 'asc')->get();
    
        $cantidadTotal = 0;
        $costoTotal = 0;
        $costoPromedio = 0;
    
        // Procesar compras para calcular el costo promedio
        foreach ($compras as $compra) {
            $cantidadTotal += $compra->cantidad;
            $costoTotal += $compra->cantidad * $compra->costo_unitario;
        }
    
        // Calcular el costo promedio antes de las ventas
        if ($cantidadTotal > 0) {
            $costoPromedio = $costoTotal / $cantidadTotal;
        }
    
        // Procesar ventas aplicando el costo promedio
        foreach ($ventas as $venta) {
            $cantidadVendida = $venta->cantidad_entregado - $venta->cantidad_devuelto;

            if ($cantidadVendida <= $cantidadTotal) {
                $cantidadTotal -= $cantidadVendida; // Reducción de la cantidad total
                $costoTotal -= $cantidadVendida * $costoPromedio; // Reducción del costo total basado en el costo promedio
            } else {
                $cantidadTotal -= $cantidadVendida; // Reducción de la cantidad total
                $costoTotal -= $cantidadVendida * $costoPromedio;
                // throw new Exception("Venta excede el inventario disponible");
            }
        }
    
        // Recalcular el costo promedio si hay inventario restante
        if ($cantidadTotal > 0) {
            $costoPromedio = $costoTotal / $cantidadTotal;
        }
    
        return $costoPromedio;
    }
    public function calcularValoresInventario(Request $request) {
        $suministros = Suministro::with(['categoria'])->get();
        // Recoge todas las transacciones en una sola consulta
        $ingresos = IngresoDetalle::whereIn('suministro_id', $suministros->pluck('id'))
                    ->orderBy('created_at', 'asc')->get()
                    ->groupBy('suministro_id');
        $ingresosDesc = IngresoDetalle::whereIn('suministro_id', $suministros->pluck('id'))
                    ->orderBy('created_at', 'desc')->get()
                    ->groupBy('suministro_id');
        
        $ventas = SolicitudDetalle::from('solicitud_detalles as sd')
                    ->join('solicituds as s','s.id','sd.solicitud_id')
                    ->where('s.estado','ENTREGADO')
                    ->whereIn('sd.suministro_id', $suministros->pluck('id'))
                    ->select('sd.*')
                    ->orderBy('sd.created_at', 'asc')->get()
                    ->groupBy('sd.suministro_id');
    
        $resultados = [];
    
        foreach ($suministros as $suministro) {
            $id = $suministro->id;
            $ingresosSuministro = $ingresos->get($id, collect());
            $ingresosSuministroDesc = $ingresosDesc->get($id, collect());
            $ventasSuministro = $ventas->get($id, collect());
    
            // $resultados[$id] = [
            //     'peps' => calcularInventarioPEPS($ingresosSuministro, $ventasSuministro),
            //     'webs' => calcularInventarioWEBS($ingresosSuministro, $ventasSuministro),
            //     'costo_promedio' => calcularCostoPromedio($ingresosSuministro, $ventasSuministro),
            // ];

            $suministro->peps = $this->calcularInventarioPEPS($ingresosSuministro, $ventasSuministro);
            $suministro->webs = $this->calcularInventarioWEBS($ingresosSuministroDesc, $ventasSuministro);
            $suministro->prom = $suministro->stock * $this->calcularCostoPromedio($ingresosSuministro, $ventasSuministro);
        }
    
        return $suministros;
    }
    public function calcularValoresInventarioPdf(Request $request) {
        $suministros = Suministro::with(['categoria'])->get();
        // Recoge todas las transacciones en una sola consulta
        $ingresos = IngresoDetalle::whereIn('suministro_id', $suministros->pluck('id'))
                    ->orderBy('created_at', 'asc')->get()
                    ->groupBy('suministro_id');
        $ingresosDesc = IngresoDetalle::whereIn('suministro_id', $suministros->pluck('id'))
                    ->orderBy('created_at', 'desc')->get()
                    ->groupBy('suministro_id');
        
        $ventas = SolicitudDetalle::from('solicitud_detalles as sd')
                    ->join('solicituds as s','s.id','sd.solicitud_id')
                    ->where('s.estado','ENTREGADO')
                    ->whereIn('sd.suministro_id', $suministros->pluck('id'))
                    ->select('sd.*')
                    ->orderBy('sd.created_at', 'asc')->get()
                    ->groupBy('sd.suministro_id');
    
        $resultados = [];
    
        foreach ($suministros as $suministro) {
            $id = $suministro->id;
            $ingresosSuministro = $ingresos->get($id, collect());
            $ingresosSuministroDesc = $ingresosDesc->get($id, collect());
            $ventasSuministro = $ventas->get($id, collect());
    
            // $resultados[$id] = [
            //     'peps' => calcularInventarioPEPS($ingresosSuministro, $ventasSuministro),
            //     'webs' => calcularInventarioWEBS($ingresosSuministro, $ventasSuministro),
            //     'costo_promedio' => calcularCostoPromedio($ingresosSuministro, $ventasSuministro),
            // ];

            $suministro->peps = $this->calcularInventarioPEPS($ingresosSuministro, $ventasSuministro);
            $suministro->webs = $this->calcularInventarioWEBS($ingresosSuministroDesc, $ventasSuministro);
            $suministro->prom = $suministro->stock * $this->calcularCostoPromedio($ingresosSuministro, $ventasSuministro);
        }
    
        $data = [
            'i' => 0,
            'title' => 'kardex de suministros',
            'totalPages' => 0,
            'datos' => $suministros,
        ];

        $pdf = PDF::loadView('reportes.pdf_inventario', $data);
        $pdf->render();
        $totalPages = $pdf->getDomPDF()->get_canvas()->get_page_count();
        $data['totalPages'] = $totalPages;
        $pdf = PDF::loadView('reportes.pdf_inventario', $data);
        // ->setPaper('letter','landscape');
        return $pdf->stream('reporte.pdf');
    }
    public function calcularInventario(Request $request) {
        $tipo = $request->get('tipo')?$request->get('tipo'):'peps';
        $suministro_id = $request->get('suministro_id');
        $suministro = Suministro::with(['categoria'])->find($suministro_id);
        $data = [
            'suministro' => $suministro,
            'operaciones' => $this->generarReporteConSaldos($tipo,$suministro_id)
        ];
        return response()->json($data, 200);
    }
    public function calcularInventarioPdf($id) {
        $tipo = 'peps';
        $config = Config::first();
        $tipo = $config->tipo_control;
        $title = 'Control de inventario con el método ' . $tipo;
        // $tipo = $request->get('tipo')?$request->get('tipo'):'peps';
        // $suministro_id = $request->get('suministro_id');
        $suministro = Suministro::with(['categoria'])->find($id);
        $data = [
            'i' => 0,
            'title' => $title,
            'totalPages' => 0,
            'tipo' => $tipo,
            'suministro' => $suministro,
            'operaciones' => $this->generarReporteConSaldos($tipo,$id)
        ];
        $pdf = PDF::loadView('reportes.pdf_inventario', $data);
        $pdf->render();
        $totalPages = $pdf->getDomPDF()->get_canvas()->get_page_count();
        $data['totalPages'] = $totalPages;
        $pdf = PDF::loadView('reportes.pdf_inventario', $data);
        // ->setPaper('letter','landscape');
        return $pdf->stream('reporte.pdf');
    }
    function generarReporteConSaldos($tipo,$id) {
        $operaciones = [];
        if ($tipo == 'peps') {
            $operaciones = $this->obtenerReportePEPS($id);
        } elseif ($tipo == 'ueps' || $tipo == 'webs') {
            $operaciones = $this->obtenerReporteWEPS($id);
        } elseif ($tipo == 'cpp') {
            $operaciones = $this->obtenerReporteCPP($id);
        }

        // Saldo inicial
        $lotes = collect(); // Colección para manejar los lotes disponibles
        $saldoUnidades = 0;
        $saldoTotal = 0;
        $costoAux = 0;

        foreach ($operaciones as $operacion) {
            if ($operacion->tipo === 'Ingreso') {
                // Añadir el lote a la colección
                $lotes->push([
                    'unidades' => $operacion->unidades,
                    'costo_unitario' => $operacion->costo_unitario,
                    'total' => $operacion->total
                ]);
                $saldoUnidades += $operacion->unidades;
                $saldoTotal += $operacion->total;
            } else if ($operacion->tipo === 'Salida') {
                // $saldoUnidades -= $operacion->unidades;

                // Restar del saldo total, asumiendo PEPS
                // Aquí simplificamos que siempre se sacan del primer costo unitario disponible
                // Se puede añadir lógica para considerar múltiples capas de costo si fuera necesario.
                // $saldoTotal -= $operacion->unidades * $operaciones->first()->costo_unitario;
                
                $unidadesRestantes = $operacion->unidades;
                $costoTotalSalida = 0;

                while ($unidadesRestantes > 0 && $lotes->isNotEmpty()) {
                    $loteIndex = ($tipo == 'peps' || $tipo == 'cpp') ? $lotes->keys()->first() : $lotes->keys()->last();
                    $lote = $lotes->get($loteIndex);

                    if ($lote['unidades'] <= $unidadesRestantes) {
                        $unidadesRestantes -= $lote['unidades'];
                        $saldoUnidades -= $lote['unidades'];
                        if ($tipo == 'cpp') {
                            $costoTotalSalida += $lote['unidades'] * $operacion->costo_promedio;
                            $saldoTotal -= $lote['unidades'] * $operacion->costo_promedio;
                        } else {
                            $costoTotalSalida += $lote['unidades'] * $lote['costo_unitario'];
                            $saldoTotal -= $lote['unidades'] * $lote['costo_unitario'];
                        }
                        // Eliminar lote agotado
                        // ($tipo == 'peps') ? $lotes->shift() : $lotes->pop();

                        // Eliminar lote agotado
                        $lotes->forget($loteIndex);
                    } else {
                        $lote['unidades'] -= $unidadesRestantes;
                        $saldoUnidades -= $unidadesRestantes;
                        if ($tipo == 'cpp') {
                            $costoTotalSalida += $unidadesRestantes * $operacion->costo_promedio;
                            $saldoTotal -= $unidadesRestantes * $operacion->costo_promedio;
                        } else {
                            $costoTotalSalida += $unidadesRestantes * $lote['costo_unitario'];
                            $saldoTotal -= $unidadesRestantes * $lote['costo_unitario'];
                        }
                        $unidadesRestantes = 0;
                        // Actualizar el lote en la colección
                        $lotes->put($loteIndex, $lote);
                    }
                }

                // Asignar el costo unitario de la salida
                $operacion->costo_unitario = $costoTotalSalida / $operacion->unidades;
                $operacion->total = $costoTotalSalida;
            }

            // Añadir saldo a la operación
            $operacion->saldo_unidades = $saldoUnidades;
            $operacion->saldo_total = $saldoTotal;
            $costoAux = $operacion->costo_unitario;
        }

        return $operaciones;
    }
    function obtenerReportePEPS($id) {
        // Consultar entradas
        $entradas = IngresoDetalle::select(
            DB::raw("'Ingreso' as tipo"),
            'created_at as fecha',
            'cantidad as unidades',
            'costo_unitario',
            DB::raw('cantidad * costo_unitario as total')
        )
        ->where('suministro_id',$id)
        ->orderBy('created_at');

        // Consultar salidas
        $salidas = SolicitudDetalle::select(
            DB::raw("'Salida' as tipo"),
            'fecha_entrega as fecha',
            'cantidad as unidades',
            DB::raw('NULL as costo_unitario'),
            DB::raw('NULL as total')
        )
        ->where('suministro_id',$id)
        ->whereNotNull('fecha_entrega')
        ->orderBy('fecha_entrega');

        // Unir las consultas y ordenarlas por fecha
        $operaciones = $entradas->unionAll($salidas)
            ->orderBy('fecha')
            ->get();

        return $operaciones;
    }
    function obtenerReporteWEPS($id) {
        $entradas = IngresoDetalle::select(
            DB::raw("'Ingreso' as tipo"),
            'created_at as fecha',
            'cantidad as unidades',
            'costo_unitario',
            DB::raw('cantidad * costo_unitario as total')
        )
        ->where('suministro_id',$id)
        ->orderBy('created_at', 'desc');
    
        $salidas = SolicitudDetalle::select(
            DB::raw("'Salida' as tipo"),
            'fecha_entrega as fecha',
            'cantidad as unidades',
            DB::raw('NULL as costo_unitario'),
            DB::raw('NULL as total')
        )
        ->where('suministro_id',$id)
        ->whereNotNull('fecha_entrega')->orderBy('fecha_entrega');
    
        $operaciones = $entradas->unionAll($salidas)
            ->orderBy('fecha')
            ->get();
    
        return $operaciones;
    }
    function obtenerReporteCPP($id) {
        $entradas = IngresoDetalle::select(
            DB::raw("'Ingreso' as tipo"),
            'created_at as fecha',
            'cantidad as unidades',
            'costo_unitario',
            DB::raw('cantidad * costo_unitario as total')
        )
        ->where('suministro_id',$id)
        ->orderBy('created_at');
    
        $salidas = SolicitudDetalle::select(
            DB::raw("'Salida' as tipo"),
            'fecha_entrega as fecha',
            'cantidad as unidades',
            DB::raw('NULL as costo_unitario'),
            DB::raw('NULL as total')
        )
        ->where('suministro_id',$id)
        ->whereNotNull('fecha_entrega')->orderBy('fecha_entrega');
    
        $operaciones = $entradas->unionAll($salidas)
            ->orderBy('fecha')
            ->get();
    
        $saldoUnidades = 0;
        $saldoTotal = 0;
        $costoPromedio = 0;
    
        foreach ($operaciones as $operacion) {
            if ($operacion->tipo === 'Ingreso') {
                $saldoUnidades += $operacion->unidades;
                $saldoTotal += $operacion->total;
                $costoPromedio = $saldoTotal / $saldoUnidades;
            } elseif ($operacion->tipo === 'Salida') {
                $saldoUnidades -= $operacion->unidades;
                $saldoTotal -= $operacion->unidades * $costoPromedio;
            }
    
            $operacion->saldo_unidades = $saldoUnidades;
            $operacion->saldo_total = $saldoTotal;
            $operacion->costo_promedio = $costoPromedio;
        }
    
        return $operaciones;
    }
}
