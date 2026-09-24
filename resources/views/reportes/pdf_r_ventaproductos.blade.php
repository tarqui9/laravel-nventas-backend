@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>
<div class="text-center">Mes: {{ $mes }} de {{ $year }}</div>

<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Código</th>
    <th>Producto</th>
    <th>Cantidad</th>
    {{-- <th>Categoría</th> --}}
    {{-- <th>Desc</th> --}}
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
        $desc += $item->descuento;
        $aux_bruto = $item->cantidad * $item->precio;
        // $total_bruto += $aux_bruto;
        $total_bruto += $item->valor_bruto;
        $total_neto += $item->valor_neto;
    @endphp
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      <td class="text-center">{{ $item->codigo }}</td>
      <td>{{ $item->nombre }}</td>
      {{-- <td>{{ $item->nombre }} $.{{ $item->precio }} </td> --}}
      <td class="text-center">{{ $item->cantidad }}</td>
      {{-- <td class="text-center">{{ $item->tipopago->descuento }}%</td> --}}
      <td class="text-center">{{ number_format($item->valor_bruto,2) }}</td>
      {{-- <td class="text-center">{{ number_format($aux_bruto,2) }}</td> --}}
      <td class="text-center">{{ number_format($item->valor_neto,2) }}</td>
      <td class="text-center">{{ number_format($item->utilidad,2) }}</td>
    </tr>
  @endforeach
  <tr class="text-center">
    <td colspan="4" class="text-right">Total</td>
    <td>{{ number_format($total_bruto,2) }}</td>
    <td>{{ number_format($total_neto,2) }}</td>
    {{-- <td>{{ number_format($desc,2) }}</td> --}}
    <td>{{ number_format($utilidad,2) }}</td>
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
