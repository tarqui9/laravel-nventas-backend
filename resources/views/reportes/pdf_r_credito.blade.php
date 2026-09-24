@extends('cr_blank')

@section('content')
<div class="text-center title">{{ $title }}</div>
@if ($ini && $fin)
  <div class="text-center">Del {{ date('d/m/Y',strtotime($ini)) }} al {{ date('d/m/Y',strtotime($fin)) }}</div>
@endif

<table class="table-data bordes">
  <tr>
    <th>N.</th>
    <th>Fecha Pedido</th>
    <th>Fecha Entrega</th>
    <th>Estado</th>
    <th>Vendedor</th>
    <th>Razon Social</th>
    {{-- <th>Categoría</th> --}}
    <th>Pagado</th>
    <th>Deuda</th>
    <th>Total</th>
  </tr>
  @php
      $pagos = 0;
      $deuda = 0;
      $total = 0;
  @endphp
  @foreach ($data as $item)
    <tr>
      <td class="text-center">{{ ++$i }}</td>
      <td class="text-center">{{ date('d-m-Y', strtotime($item->fecha)) }}</td>
      @if ($item->fecha_entrega)
        <td class="text-center">{{ date('d-m-Y H:i', strtotime($item->fecha_entrega)) }}</td>
      @else
        <td></td>
      @endif
      <td class="text-center">{{ $item->estado }}</td>
      <td>{{ $item->usuario->nombre_completo }}</td>
      <td>{{ $item->razon_social }}</td>
      <td class="text-center">{{ number_format($item->pagos,2) }}</td>
      <td class="text-center">{{ number_format($item->deuda,2) }}</td>
      <td class="text-center">{{ number_format($item->total,2) }}</td>
    </tr>
    @php
        $pagos += $item->pagos;
        $deuda += $item->deuda;
        $total += $item->total;
    @endphp
  @endforeach
  <tr class="text-center">
    <td colspan="6" class="text-right">Total</td>
    <td>{{ number_format($pagos,2) }}</td>
    <td>{{ number_format($deuda,2) }}</td>
    <td>{{ number_format($total,2) }}</td>
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
