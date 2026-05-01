@extends('sales::pdf.v2.ride-layout')

@section('ride-document-type-label', 'NOTA DE DÉBITO')

@section('ride-items')
@if($document->invoice)
    <div style="font-size:7.5px; color:#555; margin-bottom:5px; padding: 3px 5px; border: 1px solid #ddd; background:#fafafa;">
        <strong>Documento que modifica:</strong> {{ $document->invoice->code ?? '-' }}
    </div>
@endif
<table class="items-table">
    <thead>
        <tr>
            <th style="width:80%;">Razón</th>
            <th class="th-right" style="width:20%;">Valor</th>
        </tr>
    </thead>
    <tbody>
        @foreach($document->reasons ?? [] as $reason)
            <tr>
                <td>{{ $reason['reason'] ?? '-' }}</td>
                <td class="td-right">{{ number_format((float) ($reason['value'] ?? 0), 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection

@section('ride-summary')
@php
    $subtotal = (float) ($document->subtotal ?? 0);
    $taxRate  = (float) ($document->tax_rate ?? 0);
    $taxName  = $document->tax_name ?? 'IVA';
    $taxAmt   = (float) ($document->tax_amount ?? 0);
    $total    = (float) ($document->total ?? 0);
@endphp
<table class="totals-table">
    <tr>
        <td class="totals-label">Subtotal</td>
        <td class="totals-value">{{ number_format($subtotal, 2) }}</td>
    </tr>
    <tr>
        <td class="totals-label">{{ $taxName }} {{ $taxRate > 0 ? number_format($taxRate, 0).'%' : '' }}</td>
        <td class="totals-value">{{ number_format($taxAmt, 2) }}</td>
    </tr>
    <tr>
        <td class="totals-label totals-grand-label">VALOR TOTAL</td>
        <td class="totals-value totals-grand-value">{{ number_format($total, 2) }}</td>
    </tr>
</table>
@endsection
