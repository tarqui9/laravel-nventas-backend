@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>

<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Código</th>
    <th>Producto</th>
    {{-- <th>Categoría</th> --}}
    <th>Stock</th>
    <th>Utilidad</th>
  </tr>
  @php
      $sum_total = 0;
  @endphp
  @foreach ($data as $item)
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      {{-- <td class="text-center">{{ date('d-m-Y', strtotime($item->fecha)) }}</td> --}}
      <td class="text-center">{{ $item->codigo }}</td>
      <td>{{ $item->nombre }}</td>
      {{-- <td>{{ $item->categoria->nombre }}</td> --}}
      <td class="text-center">{{ $item->cantidad_st }}</td>
      <td class="text-center">{{ number_format($item->util,2) }}</td>
      @php
          $sum_total += $item->util;
      @endphp
    </tr>
  @endforeach
  <tr>
    <td class="text-left" colspan="3">TOTAL</td>
    <td class="text-center">{{ number_format($sum_total,2) }}</td>
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
