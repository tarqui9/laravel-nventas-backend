@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>
@if ($ini && $fin)
  <div class="text-center">Del {{ date('d/m/Y',strtotime($ini)) }} al {{ date('d/m/Y',strtotime($fin)) }}</div>
@endif
<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Fecha</th>
    <th>Estado</th>
    <th>Vendedor</th>
    <th>Razon Social</th>
    {{-- <th>Categoría</th> --}}
    <th>Desc</th>
    <th>T. Bruto</th>
    <th>T. Neto</th>
    <th>Utilidad</th>
  </tr>
  @php
      $utilidad = 0;
      $desc = 0;
    //   total sin ningun descuento
      $total_bruto = 0;

    //   total con descuentos
      $total_neto = 0;
  @endphp
  @foreach ($data as $item)
    @php
        $utilidad += $item->utilidad;
        if ($item->utilidad) {
          $desc += $item->descuento;
        }
        $aux_bruto = 0;
        foreach ($item->detalle as $key => $value) {
            $aux_bruto += ($value->cantidad * $value->precio);
        }
        // $total_bruto += $aux_bruto;
        $total_bruto += $item->valor_bruto;
        $total_neto += $item->total;
    @endphp
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      <td class="text-center">{{ date('d-m-Y', strtotime($item->fecha)) }}</td>
      <td class="text-center">{{ $item->estado }}</td>
      <td>{{ $item->usuario->nombre_completo }}</td>
      <td>{{ $item->razon_social }}</td>
      <td class="text-center">{{ $item->tipopago->descuento }}%</td>
      <td class="text-center">{{ number_format($item->valor_bruto,2) }}</td>
      {{-- <td class="text-center">{{ number_format($aux_bruto,2) }}</td> --}}
      <td class="text-center">{{ number_format($item->total,2) }}</td>
      @if ($item->utilidad > 0)
        <td class="text-center">{{ number_format($item->utilidad - $item->descuento,2) }}</td>
      @else
        <td class="text-center"></td>
      @endif
    </tr>
  @endforeach
  <tr class="text-center">
    <td colspan="6" class="text-right">Total</td>
    <td>{{ number_format($total_bruto,2) }}</td>
    <td>{{ number_format($total_neto,2) }}</td>
    {{-- <td>{{ number_format($desc,2) }}</td> --}}
    <td>{{ number_format($utilidad - $desc,2) }}</td>
  </tr>
</table>

    
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
  </style>
    
@endsection
