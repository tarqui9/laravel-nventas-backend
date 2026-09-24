<?php

namespace App\Http\Controllers;

use PDF;
use Auth;
use App\Models\User;
use App\Models\Stock;
use App\Models\Pedido;
use App\Models\Ingreso;
use App\Models\Agrupado;
use App\Models\Suministro;
use App\Models\PedidoSalida;
use Illuminate\Http\Request;
use App\Models\PedidoDetalle;
use App\Models\AgrupadoSalida;
use App\Models\IngresoDetalle;
use Luecano\NumeroALetras\NumeroALetras;
use Illuminate\Database\Eloquent\Collection;

class ReporteController extends Controller
{
    public function ingresoPdf($id) {
        $data = Ingreso::with(['detalle'])->find($id);
        $datos = [
            'i' => 0,
            'title' => 'nota de ingreso',
            'data' => $data,
            // 'txt_total' => $this->numberToString($data->total),
        ];

        $pdf = PDF::loadView('reportes.pdf_ingreso', $datos);
        return $pdf->stream('reporte.pdf');
    }
    public function pedidoPdf($id) {
        $data = Pedido::with(['detalle','list_pagos'])->find($id);
        foreach ($data->detalle as $key => $value) {
            $value->descuento = ((($value->cantidad * $value->precio) * $data->tipopago->descuento) / 100);
        }
        $datos = [
            'i' => 0,
            'title' => 'nota de pedido',
            'data' => $data,
            'txt_total' => $this->numberToString($data->total),
        ];

        $pdf = PDF::loadView('reportes.pdf_pedido', $datos);
        return $pdf->stream('reporte.pdf');
    }
    public function agrupadoPdf($id) {
        $data = Agrupado::with(['pedidos','detalle'])->find($id);
        foreach ($data->detalle as $key => $value) {
            $query = AgrupadoSalida::from('agrupado_salidas as as')
            ->join('ingreso_detalles as dt','dt.id','as.ingreso_detalle_id')
            ->where('dt.suministro_id',$value->suministro_id)
            ->where('as.grupo_id',$id)->sum('as.devuelto');
            $value->devuelto = $query;
        }
        $datos = [
            'i' => 0,
            'title' => 'detalle de despacho',
            'data' => $data,
            'txt_total' => $this->numberToString($data->total),
        ];

        $pdf = PDF::loadView('reportes.pdf_agrupado', $datos);
        return $pdf->stream('reporte.pdf');
    }
    public function ventaProductoPdf($id) {
        $data = Suministro::find($id);
        // foreach ($data->detalle as $key => $value) {
        //     $value->descuento = ((($value->cantidad * $value->precio) * $data->tipopago->descuento) / 100);
        // }
        $detalle = new Collection;
        $ingresos = IngresoDetalle::where('suministro_id',$id)->get();
        foreach ($ingresos as $key => $value) {
            $detalle->push([
                'fecha' => $value->ingreso->fecha,
                'cliente' => $value->ingreso->usuario->nombre_completo,
                'cantidad' => $value->cantidad,
            ]);
        }
        $ventas = PedidoDetalle::where('suministro_id',$id)->get();
        foreach ($ventas as $key => $value) {
            $detalle->push([
                'fecha' => $value->pedido->fecha,
                'cliente' => $value->pedido->cliente->razon_social,
                'cantidad' => -$value->cantidad,
            ]);
        }
        $detalle = $detalle->sortBy('fecha')->values();
        
        $sucursal_id = 1;
        $stock = Stock::where('suministro_id',$id)
        ->where('sucursal_id',$sucursal_id)->first();
        $datos = [
            'i' => 0,
            'title' => 'reporte de movimiento',
            'data' => $data,
            'detalle' => $detalle,
            'stock' => $stock,
        ];

        $pdf = PDF::loadView('reportes.pdf_movimiento', $datos);
        return $pdf->stream('reporte.pdf');
    }
    function numberToString($number) {
        $decimals = 2;
        $currency = 'Bolivianos';
        $sufijo = '';
        $cents = 'Centavos';
        $prefijo = 'SON: ';
        $formatter = new NumeroALetras;
        $formatter->apocope = true;
        $data = $formatter->toInvoice($number, $decimals, $currency);
        return $prefijo . $data . $sufijo;
    }
    // public function inscripcionesPdf($id) {
    //     $data = CategoriaTorneo::with(['torneo','categoria','inscripciones'])->find($id);
    //     $datos = [
    //         'i' => 0,
    //         'title' => 'Incripciones',
    //         'data' => $data,
    //         'categoria' => $data->categoria,
    //     ];

    //     $pdf = PDF::loadView('reportes.pdf_inscripcion', $datos);
    //     return $pdf->stream('reporte.pdf');
    // }
    public function distribucion(Request $request) {
        $rol_id = $request->get('rol_id');
        $user_id = $request->get('user_id');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if ($user_id == 'undefined') { $user_id = null; }
        if ($ini == 'undefined') { $ini = null; }
        if ($fin == 'undefined') { $fin = null; }
        if ($ini && $fin) {
            $ini = date('Y-m-d',strtotime($ini));
            $fin = date('Y-m-d',strtotime($fin));
        } else {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        }
        $data = [];
        if ($rol_id == 1 || $rol_id == 4) {
            $data = Agrupado::suser($user_id)
            ->sfechas($ini,$fin)
            ->with(['usuario','pedidos'])->get();
        }
        if ($rol_id == 3) {
            $data = Agrupado::sfechas($ini,$fin)
            ->where('user_id',Auth::user()->id)
            ->with(['usuario','pedidos'])->get();
        }
        foreach ($data as $key => $value) {
            $value->pagado = $value->pedidos->sum('pagos');
            $value->deuda = $value->pedidos->sum('deuda');
            $value->nro_pedidos = $value->pedidos->count();
            $value->nro_entregados = $value->pedidos->where('estado', 'ENTREGADO')->count();
        }

        return response()->json($data, 200);
    }
    public function distribucionPdf(Request $request) {
        $rol_id = $request->get('rol_id');
        $user_id = $request->get('user_id');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if ($user_id == 'undefined') { $user_id = null; }
        if ($ini == 'undefined') { $ini = null; }
        if ($fin == 'undefined') { $fin = null; }
        if ($ini && $fin) {
            $ini = date('Y-m-d',strtotime($ini));
            $fin = date('Y-m-d',strtotime($fin));
        } else {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        }
        $data = [];
        if ($rol_id == 1 || $rol_id == 4) {
            $data = Agrupado::suser($user_id)
            ->sfechas($ini,$fin)
            ->with(['usuario','pedidos'])->get();
        }
        if ($rol_id == 3) {
            $data = Agrupado::sfechas($ini,$fin)
            ->where('user_id',Auth::user()->id)
            ->with(['usuario','pedidos'])->get();
        }
        foreach ($data as $key => $value) {
            $value->pagado = $value->pedidos->sum('pagos');
            $value->deuda = $value->pedidos->sum('deuda');
            $value->nro_pedidos = $value->pedidos->count();
            $value->nro_entregados = $value->pedidos->where('estado', 'ENTREGADO')->count();
        }
        $datos = [
            'i' => 0,
            'title' => 'reporte de distribucion de pedidos',
            'data' => $data,
            'pedidos' => count($data)?$data->sum('nro_pedidos'):0,
            'entregados' => count($data)?$data->sum('nro_entregados'):0,
            'total' => count($data)?$data->sum('total'):0,
            'pagado' => count($data)?$data->sum('pagado'):0,
            'deuda' => count($data)?$data->sum('deuda'):0,
            'ini' => $ini,
            'fin' => $fin,
            'user_id' => $user_id,
            'usuario' => User::find($user_id),
        ];

        $pdf = PDF::loadView('reportes.pdf_r_distribucion', $datos);
        return $pdf->stream('reporte.pdf');
    }
    public function stock(Request $request) {
        $rol_id = $request->get('rol_id');
        $filter = $request->get('filter');
        $sucursal_id = 1;
        $data = Suministro::orderBy('categoria_id')->with(['categoria'])->get();
        foreach ($data as $key => $value) {
            $query = Stock::where('suministro_id',$value->id)
            ->where('sucursal_id',$sucursal_id)->first();
            if ($query) {
                $value->stock = $query->cantidad;
            } else {
                $value->stock = 0;
            }
        }
        if ($filter == 2) {
            $data = $data->filter(fn($item) => $item->stock > 0)->values();
        } elseif ($filter == 3) {
            $data = $data->filter(fn($item) => $item->stock == 0)->values();
        }

        return response()->json($data, 200);
    }
    public function stockPdf(Request $request) {
        $rol_id = $request->get('rol_id');
        $filter = $request->get('filter');
        $sucursal_id = 1;
        $data = Suministro::orderBy('categoria_id')->get();
        foreach ($data as $key => $value) {
            $query = Stock::where('suministro_id',$value->id)
            ->where('sucursal_id',$sucursal_id)->first();
            if ($query) {
                $value->stock = $query->cantidad;
            } else {
                $value->stock = 0;
            }
        }
        if ($filter == 2) {
            $data = $data->filter(fn($item) => $item->stock > 0)->values();
        } elseif ($filter == 3) {
            $data = $data->filter(fn($item) => $item->stock == 0)->values();
        }
        $datos = [
            'i' => 0,
            'title' => 'reporte de stock de productos',
            'data' => $data,
        ];

        $pdf = PDF::loadView('reportes.pdf_r_stock', $datos);
        return $pdf->stream('reporte.pdf');
    }
    
    public function utilidadProductos(Request $request) {
        $rol_id = $request->get('rol_id');
        $sucursal_id = 1;
        $data = Suministro::from('suministros as s')
        ->join('stocks as es','es.suministro_id','s.id')
        ->where('es.sucursal_id',$sucursal_id)
        ->where('es.cantidad','>',0)
        ->select('s.*')
        ->get();
        foreach ($data as $key => $value) {
            $query = Ingreso::from('ingresos as i')
            ->join('ingreso_detalles as dt','dt.ingreso_id','i.id')
            ->where('i.sucursal_id',$sucursal_id)
            ->where('dt.suministro_id',$value->id)
            ->where('dt.cantidad_stock','>',0)
            ->select('dt.*')
            ->get();
            $valor = 0;
            foreach ($query as $detail) {
                $valor += $detail->cantidad_stock * $detail->costo_unitario;
            }
            $value->util = $valor;
            $value->cantidad_st = $query->sum('cantidad_stock');
        }

        return response()->json($data, 200);
    }
    
    public function utilidadProductosPdf(Request $request) {
        $rol_id = $request->get('rol_id');
        $sucursal_id = 1;
        $data = Suministro::from('suministros as s')
        ->join('stocks as es','es.suministro_id','s.id')
        ->where('es.sucursal_id',$sucursal_id)
        ->where('es.cantidad','>',0)
        ->select('s.*')
        ->get();
        foreach ($data as $key => $value) {
            $query = Ingreso::from('ingresos as i')
            ->join('ingreso_detalles as dt','dt.ingreso_id','i.id')
            ->where('i.sucursal_id',$sucursal_id)
            ->where('dt.suministro_id',$value->id)
            ->where('dt.cantidad_stock','>',0)
            ->select('dt.*')
            ->get();
            $valor = 0;
            foreach ($query as $detail) {
                $valor += $detail->cantidad_stock * $detail->costo_unitario;
            }
            $value->util = $valor;
            $value->cantidad_st = $query->sum('cantidad_stock');
        }
        $datos = [
            'i' => 0,
            'title' => 'reporte de costo de inventario',
            'data' => $data,
        ];

        $pdf = PDF::loadView('reportes.pdf_r_utilidad', $datos);
        return $pdf->stream('reporte.pdf');
    }
    public function creditos(Request $request) {
        $rol_id = $request->get('rol_id');
        $vendedor_id = $request->get('vendedor_id');
        $cliente_id = $request->get('cliente_id');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if ($ini && $fin) {
            $ini = date('Y-m-d',strtotime($ini));
            $fin = date('Y-m-d',strtotime($fin));
        } else {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        }
        $sucursal_id = 1;
        $data = Pedido::where('sucursal_id',$sucursal_id)
        ->scliente($cliente_id)
        ->suser($vendedor_id)
        ->where('deuda','>',0)
        ->whereDate('fecha','>=',$ini)
        ->whereDate('fecha','<=',$fin)
        ->with(['usuario','cliente','tipopago'])
        ->get();
        // $data = Pedido::where('tipo','Contado')->where('sucursal_id',$sucursal_id)->get();
        return response()->json($data, 200);;
    }
    public function creditosPdf(Request $request) {
        $rol_id = $request->get('rol_id');
        $vendedor_id = $request->get('vendedor_id');
        $cliente_id = $request->get('cliente_id');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if ($ini && $fin) {
            $ini = date('Y-m-d',strtotime($ini));
            $fin = date('Y-m-d',strtotime($fin));
        } else {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        }
        $sucursal_id = 1;
        $data = Pedido::where('sucursal_id',$sucursal_id)
        ->scliente($cliente_id)
        ->suser($vendedor_id)
        ->where('deuda','>',0)
        ->whereDate('fecha','>=',$ini)
        ->whereDate('fecha','<=',$fin)
        ->get();
        // $data = Pedido::where('tipo','Contado')->where('sucursal_id',$sucursal_id)->get();
        foreach ($data as $key => $value) {
        }
        $datos = [
            'i' => 0,
            'title' => 'reporte de creditos',
            'data' => $data,
            'ini' => $ini,
            'fin' => $fin,
        ];

        $pdf = PDF::loadView('reportes.pdf_r_credito', $datos);
        return $pdf->stream('reporte.pdf');
    }
    public function ventas(Request $request) {
        $rol_id = $request->get('rol_id');
        $vendedor_id = $request->get('vendedor_id');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if (!$ini || !$fin) {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        } else {
            $ini = date('Y-m-d', strtotime($ini));
            $fin = date('Y-m-d', strtotime($fin));
        }
        $sucursal_id = 1;
        $data = Pedido::where('sucursal_id',$sucursal_id)
        ->whereDate('fecha','>=',$ini)
        ->whereDate('fecha','<=',$fin)
        ->suser($vendedor_id)
        ->with(['usuario','tipopago'])
        ->get();
        // $data = Pedido::where('tipo','Contado')->where('sucursal_id',$sucursal_id)->get();
        foreach ($data as $key => $value) {
            $value->valor_bruto = $this->valorBruto($value->id);
            $value->utilidad = $this->utilidad($value->id);
            // if (!$value->valor_bruto) {
            //     $value->utilidad = 0;
            // }
        }
        return response()->json($data, 200);
    }
    public function ventasPdf(Request $request) {
        // return response()->json('$data', 200);
        $rol_id = $request->get('rol_id');
        $vendedor_id = $request->get('vendedor_id');
        $ini = $request->get('fecha_inicio');
        $fin = $request->get('fecha_fin');
        if (!$ini || !$fin) {
            $ini = date('Y-m-d');
            $fin = date('Y-m-d');
        } else {
            $ini = date('Y-m-d', strtotime($ini));
            $fin = date('Y-m-d', strtotime($fin));
        }
        $sucursal_id = 1;
        $data = Pedido::where('sucursal_id',$sucursal_id)
        ->whereDate('fecha','>=',$ini)
        ->whereDate('fecha','<=',$fin)
        ->suser($vendedor_id)
        ->get();
        // $data = Pedido::where('tipo','Contado')->where('sucursal_id',$sucursal_id)->get();
        foreach ($data as $key => $value) {
            $value->valor_bruto = $this->valorBruto($value->id);
            $value->utilidad = $this->utilidad($value->id);
        }
        $datos = [
            'i' => 0,
            'title' => 'reporte de ventas',
            'data' => $data,
            'ini' => $ini,
            'fin' => $fin,
        ];

        $pdf = PDF::loadView('reportes.pdf_r_ventas', $datos);
        return $pdf->stream('reporte.pdf');
    }
    function valorBruto($id) {
        $valor = 0;
        $pedido = Pedido::find($id);
        foreach ($pedido->dtsalida as $key => $value) {
            $costo = IngresoDetalle::find($value->ingreso_detalle_id);
            $valor += ($value->cantidad * $costo->costo_unitario);
        }
        return $valor;
    }
    function utilidad($id) {
        $util = 0;
        $pedido = Pedido::find($id);
        foreach ($pedido->dtsalida as $key => $value) {
            $costo = IngresoDetalle::find($value->ingreso_detalle_id);
            $util += ($value->cantidad * $value->precio) - ($value->cantidad * $costo->costo_unitario);
        }
        return $util;
    }
    public function ventaproductos(Request $request) {
        $mes = $request->get('mes');
        $year = $request->get('year');
        $rol_id = $request->get('rol_id');
        $sucursal_id = 1;
        $data = Suministro::from('suministros as s')
        ->join('pedido_detalles as dt','dt.suministro_id','s.id')
        ->join('pedidos as p','p.id','dt.pedido_id')
        ->where('p.sucursal_id',$sucursal_id)
        ->whereMonth('p.fecha',$mes)
        ->whereYear('p.fecha',$year)
        ->select('s.*')
        ->distinct('s.id')->get();
        // return $data;
        // $data = Pedido::where('tipo','Contado')->where('sucursal_id',$sucursal_id)->get();
        foreach ($data as $key => $value) {
            $query = PedidoSalida::from('pedido_salidas as ps')
            // ->from('pedido_detalles as dt')
            ->join('ingreso_detalles as dt','dt.id','ps.ingreso_detalle_id')
            ->join('pedidos as p','p.id','ps.pedido_id')
            ->join('tipo_pagos as t','t.id','p.tipo_pago_id')
            ->where('dt.suministro_id',$value->id)
            ->whereMonth('p.fecha',$mes)
            ->whereYear('p.fecha',$year)
            ->select('ps.*')->get();
            $value->valor_bruto = $this->valorBrutoProducto($value->id, $query);
            $value->valor_neto = $this->valorNetoProducto($value->id, $query);
            $value->utilidad = $this->utilidadProducto($value->id, $query);
            $value->cantidad = $query->sum('cantidad');
        }
        return response()->json($data, 200);
    }
    public function ventaproductosPdf(Request $request) {
        // return response()->json('$data', 200);
        $mes = $request->get('mes');
        $year = $request->get('year');
        // $year = date('Y');
        $rol_id = $request->get('rol_id');
        $sucursal_id = 1;
        $data = Suministro::from('suministros as s')
        ->join('pedido_detalles as dt','dt.suministro_id','s.id')
        ->join('pedidos as p','p.id','dt.pedido_id')
        ->where('p.sucursal_id',$sucursal_id)
        ->whereMonth('p.fecha',$mes)
        ->whereYear('p.fecha',$year)
        ->select('s.*')
        ->distinct('s.id')->get();
        // return $data;
        // $data = Pedido::where('tipo','Contado')->where('sucursal_id',$sucursal_id)->get();
        foreach ($data as $key => $value) {
            $query = PedidoSalida::from('pedido_salidas as ps')
            // ->from('pedido_detalles as dt')
            ->join('ingreso_detalles as dt','dt.id','ps.ingreso_detalle_id')
            ->join('pedidos as p','p.id','ps.pedido_id')
            ->join('tipo_pagos as t','t.id','p.tipo_pago_id')
            ->where('dt.suministro_id',$value->id)
            ->whereMonth('p.fecha',$mes)
            ->whereYear('p.fecha',$year)
            ->select('ps.*')->get();
            $value->valor_bruto = $this->valorBrutoProducto($value->id, $query);
            $value->valor_neto = $this->valorNetoProducto($value->id, $query);
            $value->utilidad = $this->utilidadProducto($value->id, $query);
            $value->cantidad = $query->sum('cantidad');
        }
        $datos = [
            'i' => 0,
            'title' => 'reporte de venta por productos',
            'data' => $data,
            'year' => $year,
            'mes' => $this->obtenerNombreMes($mes),
        ];

        $pdf = PDF::loadView('reportes.pdf_r_ventaproductos', $datos);
        return $pdf->stream('reporte.pdf');
    }
    function obtenerNombreMes($mes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $meses[$mes] ?? 'Mes inválido'; // Devuelve el nombre o "Mes inválido" si el número es incorrecto
    }
    function valorBrutoProducto($id, $query) {
        $valor = 0;
        // $pedido = PedidoDetalle::where('suministro_id',$id)->get();
        foreach ($query as $key => $value) {
            $costo = IngresoDetalle::find($value->ingreso_detalle_id);
            // $costo = IngresoDetalle::where('suministro_id',$value->suministro_id)->first();
            $valor += ($value->cantidad * $costo->costo_unitario);
        }
        return $valor;
    }
    function valorNetoProducto($id, $query) {
        $valor = 0;
        // $pedido = PedidoDetalle::from('pedido_detalles as dt')
        // ->join('pedidos as p','p.id','dt.pedido_id')
        // ->join('tipo_pagos as t','t.id','p.tipo_pago_id')
        // ->where('suministro_id',$id)
        // ->select('dt.*','t.descuento')->get();
        foreach ($query as $key => $value) {
            $valor += (($value->cantidad * $value->precio) - $value->descuento);
        }
        return $valor;
    }
    function utilidadProducto($id, $query) {
        $util = 0;
        // $pedido = PedidoDetalle::from('pedido_detalles as dt')
        // ->join('pedidos as p','p.id','dt.pedido_id')
        // ->join('tipo_pagos as t','t.id','p.tipo_pago_id')
        // ->where('suministro_id',$id)
        // ->select('dt.*','t.descuento')->get();
        foreach ($query as $key => $value) {
            $costo = IngresoDetalle::find($value->ingreso_detalle_id);
            // $costo = IngresoDetalle::where('suministro_id',$value->suministro_id)->first();
            $neto = (($value->cantidad * $value->precio) - $value->descuento);
            $util += ($neto - ($value->cantidad * $costo->costo_unitario));
        }
        return $util;
    }
    function cantidadProducto($id, $query) {
        // $pedido = PedidoDetalle::where('suministro_id',$id)->sum('cantidad');
        return $query->sum('cantidad');
    }
    public function gestiones() {
        $year = date('Y');
        $newCollection = new Collection;
        while ($year >= 2025) {
            $newCollection->push(['year' => $year]);
            $year--;
        }
        return response()->json($newCollection, 200);
    }
}
