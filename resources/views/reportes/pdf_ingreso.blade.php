@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>
<div>Fecha de ingreso: {{ date('d/m/Y', strtotime($data->fecha)) }}</div>
<div>Pedido realizado por: {{ $data->usuario->nombre_completo }}</div>
<div>Datos del proveedor:</div>
<div>Nombre: {{ $data->proveedor->nombre }}</div>
<div>Razón social: {{ $data->proveedor->razon_social }}</div>
<div>NIT: {{ $data->proveedor->nit }}</div>
<div>Teléfono: {{ $data->proveedor->telefono }}</div>
<div>Ciudad: {{ $data->proveedor->ciudad }}</div>
<div>País: {{ $data->proveedor->pais }}</div>
<div>Dirección: {{ $data->proveedor->direccion }}</div>
<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Lote</th>
    <th>Detalle</th>
    <th>Cantidad</th>
    <th>C.U.</th>
    <th>F. Vencimiento</th>
    <th>Total</th>
  </tr>
  @foreach ($data->detalle as $item)
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      <td class="text-center">{{ $item->lote }}</td>
      <td>{{ $item->suministro->nombre }}</td>
      <td class="text-center">{{ $item->cantidad }}</td>
      <td class="text-center">{{ number_format($item->costo_unitario,2) }}</td>
      <td class="text-center">{{ date('d-m-Y', strtotime($item->fecha_vencimiento)) }}</td>
      <td class="text-center">{{ number_format($item->cantidad * $item->costo_unitario,2) }}</td>
    </tr>
  @endforeach
    {{-- <tr>
        <td class="text-right no-margin-bottom no-margin-left negrita" colspan="4">TOTAL</td>
        <td class="text-center negrita">{{ number_format($data->total,2) }}</td>
    </tr> --}}
</table>

{{-- <div>{{ $txt_total }}</div> --}}
    
@endsection

@section('scripts')
  <style>
    .title {
      font-size: 18px;
      font-weight: bold;
      text-transform: uppercase;
    }
    .table-data {
      width: 100%;
    }
    .bordes .no-margin {
      margin-top: 0;
      margin-bottom: 0;
      margin-left: 0;
      border: none;
    }
    .bordes .no-margin-top {
      margin-top: 0;
      border: none;
    }
    .bordes .no-margin-bottom {
      margin-bottom: 0;
      border: none;
    }
    .bordes .no-margin-left {
      margin-left: 0;
      border: none;
    }
  </style>
    
@endsection
