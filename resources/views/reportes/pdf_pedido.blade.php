@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>
<table class="table-data">
  <tr>
    <td>Fecha: {{ date('d/m/Y H:i', strtotime($data->fecha)) }}</td>
    <td></td>
  </tr>
  <tr>
    <td>Nombre/Razón Social: {{ $data->razon_social }}</td>
    <td>NIT/CI: {{ $data->nit_ci }}</td>
  </tr>
</table>
{{-- <div>NIT/CI: {{ $data->nit_ci }}</div>
<div>Tipo Venta: {{ $data->tipo }}</div>
<div>Pedido realizado por: {{ $data->usuario->nombre_completo }}</div>
<div>Datos del cliente:</div>
<div>Contacto: {{ $data->cliente->contacto }}</div>
<div>Dirección: {{ $data->cliente->direccion }}</div>
<div>Celular: {{ $data->cliente->celular }}</div>
<div>Telefono: {{ $data->cliente->telefono }}</div> --}}
<table class="table-data sin-bordes">
  <tr class="text-upper">
    <th>N.</th>
    <th>Código</th>
    <th>Descripción</th>
    <th>Cantidad</th>
    <th>Precio</th>
    <th>Descuento</th>
    <th>SubTotal</th>
  </tr>
  @foreach ($data->detalle as $item)
    <tr>
      <td class="text-center no-margin">{{ ++$i }}</td>
      {{-- <td class="text-center no-margin">{{ date('d-m-Y', strtotime($item->fecha)) }}</td> --}}
      <td class="text-center no-margin">{{ $item->suministro->codigo }}</td>
      <td class="no-margin">{{ $item->suministro->nombre }}</td>
      <td class="text-center no-margin">{{ $item->cantidad }}</td>
      <td class="text-center no-margin">{{ number_format($item->precio,2) }}</td>
      <td class="text-center no-margin">{{ number_format($item->descuento,2) }}</td>
      <td class="text-center no-margin">{{ number_format($item->total - $item->descuento,2) }}</td>
    </tr>  
  @endforeach
  @if ($data->descuento)
    {{-- <tr>
        <td class="text-right no-margin-bottom no-margin-left" colspan="4">Total</td>
        <td class="text-center">{{ number_format($data->subtotal,2) }}</td>
    </tr> --}}
    {{-- <tr>
        <td class="text-right no-margin" colspan="4">Descuento</td>
        <td class="text-center">{{ number_format($data->descuento,2) }}</td>
    </tr> --}}
    <tr>
        <td class="text-right no-margin-bottom no-margin-left negrita" colspan="6">TOTAL</td>
        <td class="text-center negrita">{{ number_format($data->total,2) }}</td>
    </tr>
  @else
    <tr>
        <td class="text-right no-margin-bottom no-margin-left negrita" colspan="6">TOTAL</td>
        <td class="text-center negrita">{{ number_format($data->total,2) }}</td>
    </tr>
  @endif
</table>

<div class="text">{{ $txt_total }}</div>

@if (count($data->list_pagos))
  <div class="text-center text">Pagos realizados</div>
  <table class="table-data sin-bordes">
    <tr>
      <th>N.</th>
      <th>Fecha</th>
      <th>Monto</th>
    </tr>
    @php
        $i = 0;
        $total_pagos = 0;
    @endphp
    @foreach ($data->list_pagos as $item)
      <tr>
        <td class="text-center no-margin">{{ ++$i }}</td>
        <td class="text-center no-margin">{{ date('d/m/Y', strtotime($item->fecha)) }}</td>
        <td class="text-center no-margin">{{ number_format($item->monto,2) }}</td>
      </tr>
      @php
          $total_pagos += $item->monto;
      @endphp
    @endforeach
    <tr>
      <td colspan="2">Total</td>
      <td class="text-center">{{ number_format($total_pagos,2) }}</td>
    </tr>
  </table>
@endif
    
@endsection

@section('scripts')
  <style>
    table {
      font-size: 12px;
    }
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
    .sin-bordes th {
      border: 1px solid black;
      padding: 2px;
    }

    .sin-bordes td {
      border-left: 1px solid black;
      border-right: 1px solid black;
      padding: 2px;
    }

    .sin-bordes tr {
      border-top: none;
      border-bottom: none;
    }
    .sin-bordes tr:last-child td {
      border: 1px solid black;
    }
    .text {
      font-size: 12px;
    }
    .text-upper {
      text-transform: uppercase;
    }
  </style>
    
@endsection
