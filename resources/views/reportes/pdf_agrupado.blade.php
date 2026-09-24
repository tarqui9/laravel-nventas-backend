@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>

<div>Fecha: {{ date('d-m-Y', strtotime($data->fecha)) }}</div>
<div>{{ $data->estado }}</div>
<div>Distribuidor: {{ $data->usuario->nombre_completo }}</div>

<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Detalle</th>
    <th>Cantidad</th>
    <th>Devuelto</th>
    <th>Total</th>
  </tr>
  @foreach ($data->detalle as $item)
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      {{-- <td class="text-center">{{ date('d-m-Y', strtotime($item->fecha)) }}</td> --}}
      <td>{{ $item->suministro->nombre }}</td>
      <td class="text-center">{{ $item->cantidad }}</td>
      <td class="text-center">{{ $item->devuelto }}</td>
      <td class="text-center">{{ number_format($item->total,2) }}</td>
    </tr>  
  @endforeach
  <tr>
      <td class="text-right negrita" colspan="4">TOTAL</td>
      <td class="text-center negrita">{{ number_format($data->total,2) }}</td>
  </tr>
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
  </style>
    
@endsection
