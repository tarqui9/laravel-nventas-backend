@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>
@if ($ini && $fin)
  <div>Del {{ date('d/m/Y',strtotime($ini)) }} al {{ date('d/m/Y',strtotime($fin)) }}</div>
@endif
@if ($usuario)
  <div>Distribuidor: {{ $usuario->nombre_completo }}</div>
  {{-- <div>Distribuidor: {{ $user_id }}</div> --}}
  {{-- <div>Distribuidor: {{ $user->nombre_completo }} {{ $user_id }}</div> --}}
@endif

<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Fecha</th>
    <th>Estado</th>
    <th>Distribuidor</th>
    <th>Nro. Pedidos</th>
    <th>Nro. Entregas</th>
    <th>Pagado</th>
    <th>Deuda</th>
    <th>Total</th>
  </tr>
  @foreach ($data as $item)
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      <td class="text-center">{{ date('d-m-Y', strtotime($item->fecha)) }}</td>
      <td>{{ $item->estado }}</td>
      <td>{{ $item->usuario->nombre_completo }}</td>
      <td class="text-center">{{ $item->nro_pedidos }}</td>
      <td class="text-center">{{ $item->nro_entregados }}</td>
      <td class="text-center">{{ number_format($item->pagado,2) }}</td>
      <td class="text-center">{{ number_format($item->deuda,2) }}</td>
      <td class="text-center">{{ number_format($item->total,2) }}</td>
    </tr>  
  @endforeach
    <tr>
        <td class="text-right" colspan="4">Total</td>
        <td class="text-center">{{ $pedidos }}</td>
        <td class="text-center">{{ $entregados }}</td>
        <td class="text-center">{{ number_format($pagado,2) }}</td>
        <td class="text-center">{{ number_format($deuda,2) }}</td>
        <td class="text-center">{{ number_format($total,2) }}</td>
    </tr>
  {{-- <tr>
      <td class="text-right negrita" colspan="4">TOTAL</td>
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
  </style>
    
@endsection
