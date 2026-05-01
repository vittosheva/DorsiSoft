@extends('sales::pdf.v2.ride-layout')

@section('ride-document-type-label', 'NOTA DE CRÉDITO')

@section('ride-items')
@if($document->invoice)
    <div style="font-size:7.5px; color:#555; margin-bottom:5px; padding: 3px 5px; border: 1px solid #ddd; background:#fafafa;">
        <strong>Documento que modifica:</strong> {{ $document->invoice->code ?? '-' }}
        @if($document->reason_code)
            &nbsp;&mdash;&nbsp; Motivo: {{ $document->reason_code?->getLabel() }}
        @endif
    </div>
@endif
<table class="items-table">
    <thead>
        <tr>
            <th style="width:12%;">Código</th>
            <th style="width:44%;">Descripción</th>
            <th class="th-center" style="width:10%;">Cantidad</th>
            <th class="th-right" style="width:14%;">Precio U.</th>
            <th class="th-right" style="width:20%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($document->items as $item)
            <tr>
                <td>{{ $item->product_code ?? '-' }}</td>
                <td>
                    {{ $item->product_name ?? $item->description ?? '' }}
                    @if(filled($item->description) && $item->description !== $item->product_name)
                        <div style="font-size:7px; color:#666; margin-top:1px;">{{ $item->description }}</div>
                    @endif
                </td>
                <td class="td-center">{{ rtrim(rtrim(number_format((float) $item->quantity, 6, '.', ''), '0'), '.') }}</td>
                <td class="td-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="td-right">{{ number_format((float) $item->total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection

@section('ride-summary')
@php
    $subtotal = (float) ($document->subtotal ?? 0);
    $taxAmt   = (float) ($document->tax_amount ?? 0);
    $total    = (float) ($document->total ?? 0);
@endphp
<table class="totals-table">
    <tr>
        <td class="totals-label">Subtotal</td>
        <td class="totals-value">{{ number_format($subtotal, 2) }}</td>
    </tr>
    <tr>
        <td class="totals-label">IVA</td>
        <td class="totals-value">{{ number_format($taxAmt, 2) }}</td>
    </tr>
    <tr>
        <td class="totals-label totals-grand-label">VALOR TOTAL</td>
        <td class="totals-value totals-grand-value">{{ number_format($total, 2) }}</td>
    </tr>
</table>
@endsection
