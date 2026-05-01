@extends('sales::pdf.v2.ride-layout')

@section('ride-document-type-label', 'FACTURA')

@section('ride-items')
<table class="items-table">
    <thead>
        <tr>
            <th style="width:12%;">Código</th>
            <th style="width:38%;">Descripción</th>
            <th class="th-center" style="width:10%;">Cantidad</th>
            <th class="th-right" style="width:13%;">Precio U.</th>
            <th class="th-right" style="width:13%;">Descuento</th>
            <th class="th-right" style="width:14%;">Total</th>
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
                <td class="td-right">{{ number_format((float) ($item->discount_amount ?? 0), 2) }}</td>
                <td class="td-right">{{ number_format((float) $item->total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endsection

@section('ride-summary')
@php
    $subtotal = (float) ($document->subtotal ?? 0);
    $taxBase  = (float) ($document->tax_base ?? 0);
    $discount = (float) ($document->discount_amount ?? 0);
    $taxAmt   = (float) ($document->tax_amount ?? 0);
    $total    = (float) ($document->total ?? 0);
    $base0    = $subtotal - $taxBase;
@endphp
<table class="totals-table">
    <tr>
        <td class="totals-label">Subtotal 0%</td>
        <td class="totals-value">{{ number_format($base0 > 0 ? $base0 : 0, 2) }}</td>
    </tr>
    <tr>
        <td class="totals-label">Subtotal IVA</td>
        <td class="totals-value">{{ number_format($taxBase, 2) }}</td>
    </tr>
    @if($discount > 0)
        <tr>
            <td class="totals-label" style="color:#cc0000;">Descuento</td>
            <td class="totals-value" style="color:#cc0000;">-{{ number_format($discount, 2) }}</td>
        </tr>
    @endif
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
