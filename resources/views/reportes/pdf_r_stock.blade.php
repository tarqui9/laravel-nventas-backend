@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>

<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Categoría</th>
    <th>Código</th>
    <th>Producto</th>
    <th>Precio</th>
    {{-- <th>Categoría</th> --}}
    <th>Stock</th>
    <th>Cantidad Jabas</th>
  </tr>
  @foreach ($data as $item)
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      {{-- <td class="text-center">{{ date('d-m-Y', strtotime($item->fecha)) }}</td> --}}
      <td class="text-center">{{ $item->categoria->nombre }}</td>
      <td class="text-center">{{ $item->codigo }}</td>
      <td>{{ $item->nombre }}</td>
      <td class="text-center">{{ number_format($item->precio,2) }}</td>
      {{-- <td>{{ $item->categoria->nombre }}</td> --}}
      <td class="text-center">{{ $item->stock }}</td>
      <td class="text-center">{{ number_format($item->stock / $item->cantidad_unidades,2) }}</td>
    </tr>
  @endforeach
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
