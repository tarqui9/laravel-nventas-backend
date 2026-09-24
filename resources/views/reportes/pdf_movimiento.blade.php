@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>
<div>Producto: {{ $data->nombre }}</div>
<div>Categoría: {{ $data->categoria->nombre }}</div>
<div>Código: {{ $data->codigo }}</div>
<div>Stock actual: {{ $stock->cantidad }}</div>
<div class="text-center">Detalle</div>
<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Fecha</th>
    <th>Cliente/Usuario</th>
    <th>Cantidad</th>
  </tr>
  @foreach ($detalle as $item)
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      <td class="text-center">{{ date('d/m/Y', strtotime($item['fecha'])) }}</td>
      <td>{{ $item['cliente'] }}</td>
      <td class="text-center">{{ $item['cantidad'] }}</td>
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
