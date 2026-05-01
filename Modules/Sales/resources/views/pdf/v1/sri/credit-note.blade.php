@extends('sales::pdf.layouts.dorsi')

@section('title', __('Credit Note') . ' ' . $document->code)
@section('doc-type', __('Credit Note'))

@section('doc-status')
    <div class="status-badge status-{{ $document->status->value }}">{{ $document->status->getLabel() }}</div>
@endsection

@section('logo-company')
    @include('sales::pdf.partials.sri-logo-company', ['document' => $document])
@endsection

@section('doc-number-area')
    @include('sales::pdf.partials.sri-barcode', ['document' => $document])
@endsection

@section('content')
    @php
        $pdfDateFormatter = \Modules\Core\Support\Pdf\PdfDateFormatter::class;
    @endphp

    {{-- Billing Section --}}
    <table class="billing-table">
        <tr>
            <td class="billing-left">
                <div class="customer-name">{{ $document->customer_name }}</div>
                @if($document->customer_identification)
                    <div class="customer-detail">{{ $document->customer_identification }}</div>
                @endif
                @if($document->customer_address)
                    <div class="customer-detail">{{ $document->customer_address }}</div>
                @endif
                @if($document->customer_email)
                    <div class="customer-detail">{{ is_array($document->customer_email) ? ($document->customer_email[0] ?? '') : $document->customer_email }}</div>
                @endif
            </td>
            <td class="billing-right">
                <table class="detail-table">
                    <tr>
                        <td class="detail-key">{{ __('Issue date') }}</td>
                        <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->issue_date) }}</td>
                    </tr>
                    <tr>
                        <td class="detail-key">{{ __('Currency') }}</td>
                        <td class="detail-val">{{ $document->currency_code ?? 'USD' }}</td>
                    </tr>
                    @if($document->invoice?->code)
                        <tr>
                            <td class="detail-key">{{ __('Invoice') }}</td>
                            <td class="detail-val">{{ $document->invoice->code }} / {{ $document->invoice->establishment_code }}-{{ $document->invoice->emission_point_code }}-{{ $document->invoice->sequential_number }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="detail-key">{{ __('Reason') }}</td>
                        <td class="detail-val">{{ $document->reason }}</td>
                    </tr>
                    {{-- @include('sales::pdf.partials.sri-detail-rows', ['document' => $document]) --}}
                </table>
            </td>
        </tr>
    </table>

    {{-- ─── Items Table ─── --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="th-center">#</th>
                <th style="width:11%;">{{ __('Code') }}</th>
                <th>{{ __('Description') }}</th>
                <th style="width:8%;" class="th-center">{{ __('Qty') }}</th>
                <th style="width:11%;" class="th-right">{{ __('U. price') }}</th>
                <th style="width:10%;" class="th-right">{{ __('Discount') }}</th>
                <th style="width:11%;" class="th-right">{{ __('Subtotal') }}</th>
                <th style="width:9%;" class="th-right">{{ __('Taxes') }}</th>
                <th style="width:11%;" class="th-right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
                <tr @if($loop->even) class="row-alt" @endif>
                    <td class="td-center">{{ $loop->iteration }}</td>
                    <td><span class="item-code">{{ $item->product_code }}</span></td>
                    <td>
                        {{ $item->product_name }}
                        @if(filled($item->description) && trim((string) $item->description) !== trim((string) $item->product_name))
                            <div class="item-description">{{ $item->description }}</div>
                        @endif
                        @if(filled($item->detail_1))
                            <div class="item-description">{{ $item->detail_1 }}</div>
                        @endif
                        @if(filled($item->detail_2))
                            <div class="item-description">{{ $item->detail_2 }}</div>
                        @endif
                    </td>
                    <td class="td-center nowrap">{{ number_format((float) $item->quantity, 2) }} {{ $item->product_unit }}</td>
                    <td class="td-right">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="td-right">
                        @if($item->discount_amount > 0)
                            {{ number_format((float) $item->discount_amount, 2) }}
                        @else
                            <span class="em-dash">&mdash;</span>
                        @endif
                    </td>
                    <td class="td-right">{{ number_format((float) $item->subtotal, 2) }}</td>
                    <td class="td-right">{{ number_format((float) $item->tax_amount, 2) }}</td>
                    <td class="td-right">{{ number_format((float) $item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ─── Totals + Notes ─── --}}
    <table class="bottom-table">
        <tr>
            <td class="notes-cell">
                @if(!empty($document->notes) && $document->notes != '<p></p>')
                    <div class="notes-title">{{ __('Notes') }}</div>
                    <div class="notes-body">{!! nl2br(e($document->notes)) !!}</div>
                @endif
            </td>
            <td class="totals-cell">
                <table class="totals-table">
                    <tr>
                        <td class="totals-row-label">{{ __('Subtotal') }}</td>
                        <td class="totals-row-value">{{ number_format((float) $document->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-discount-label">{{ __('Total discount') }}</td>
                        <td class="totals-discount-value">{{ number_format((float) $totalDiscountAmount, 2) }}</td>
                    </tr>
                    @foreach($taxBreakdown ?? [] as $taxRow)
                        <tr>
                            <td class="totals-row-label">{{ $taxRow['label'] }}</td>
                            <td class="totals-row-value">{{ number_format((float) $taxRow['tax_amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td class="totals-row-label">{{ __('Tax') }}</td>
                        <td class="totals-row-value">{{ number_format((float) $document->tax_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="totals-grand-label">{{ __('Total') }} {{ $document->currency_code ?? 'USD' }}</td>
                        <td class="totals-grand-value">{{ number_format((float) $document->total, 2) }}</td>
                    </tr>
                    @if($document->applied_amount > 0)
                        <tr>
                            <td class="totals-paid-label">{{ __('Applied') }}</td>
                            <td class="totals-paid-value">{{ number_format((float) $document->applied_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if($document->refunded_amount > 0)
                        <tr>
                            <td class="totals-paid-label">{{ __('Refunded') }}</td>
                            <td class="totals-paid-value">{{ number_format((float) $document->refunded_amount, 2) }}</td>
                        </tr>
                    @endif
                    @php
                        $remaining = $document->getRemainingBalance();
                    @endphp
                    @if((float) $remaining > 0)
                        <tr>
                            <td class="totals-due-label">{{ __('Remaining credit') }}</td>
                            <td class="totals-due-value">{{ number_format((float) $remaining, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

@endsection
