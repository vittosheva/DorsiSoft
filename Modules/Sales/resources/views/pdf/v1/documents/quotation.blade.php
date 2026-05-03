@extends('sales::pdf.layouts.dorsi')

@section('title', __('Quotation') . ' ' . $document->code)
@section('doc-type', __('Quotation'))

@section('doc-status')
    <div class="status-badge status-{{ $document->status->value }}">{{ $document->status->getLabel() }}</div>
@endsection

@section('logo-company')
    @include('sales::pdf.partials.sri-logo-company', ['document' => $document->company])
@endsection

@section('doc-number-area')
    <div class="doc-number-label">{{ $document->code }}</div>
@endsection

@section('content')
    @php($pdfDateFormatter = \Modules\Core\Support\Pdf\PdfDateFormatter::class)

    {{-- ─── Billing Section ─── --}}
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
                        <td class="detail-key">{{ __('Issue Date') }}</td>
                        <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->issue_date) }}</td>
                    </tr>
                    <tr>
                        <td class="detail-key">{{ __('Valid until') }}</td>
                        <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->expires_at) }}</td>
                    </tr>
                    <tr>
                        <td class="detail-key">{{ __('Currency') }}</td>
                        <td class="detail-val">{{ $document->currency_code ?? 'USD' }}</td>
                    </tr>
                    {{-- @if($document->priceList?->name)
                        <tr>
                            <td class="detail-key">{{ __('Price List') }}</td>
                            <td class="detail-val">{{ $document->priceList->name }}</td>
                        </tr>
                    @endif --}}
                    @if($document->seller_name)
                        <tr>
                            <td class="detail-key">{{ __('Seller') }}</td>
                            <td class="detail-val">{{ $document->seller_name }}</td>
                        </tr>
                    @endif
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
                @if(!empty($document->introduction) && $document->introduction != '<p></p>')
                    <div class="notes-title">{{ __('Terms and conditions') }}</div>
                    <div class="notes-body" style="margin-bottom:10px;">{!! $document->introduction !!}</div>
                @endif
                @if(!empty($document->notes) && $document->notes != '<p></p>')
                    <div class="notes-title">{{ __('Notes') }}</div>
                    <div class="notes-body">{!! $document->notes !!}</div>
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
                </table>
            </td>
        </tr>
    </table>
@endsection
