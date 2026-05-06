@extends('sales::pdf.layouts.dorsi')

@section('title', __('Sale Note') . ' ' . $document->code)
@section('doc-type', __('Sale Note'))

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
                        <td class="detail-key">{{ __('Issue Date') }}</td>
                        <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->issue_date) }}</td>
                    </tr>
                    <tr>
                        <td class="detail-key">{{ __('Currency') }}</td>
                        <td class="detail-val">{{ $document->currency_code ?? 'USD' }}</td>
                    </tr>
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

    {{-- Items Table --}}
    @include('sales::pdf.partials.sri-detail-rows')

    {{-- Taxes Breakdown --}}
    @if(isset($taxBreakdown) && count($taxBreakdown) > 0)
        <div class="tax-breakdown">
            <h4>{{ __('Taxes Breakdown') }}</h4>
            <table class="tax-table">
                @foreach($taxBreakdown as $tax)
                    <tr>
                        <td>{{ $tax['tax_type'] }} ({{ $tax['tax_rate'] }}%)</td>
                        <td style="text-align: right;">{{ number_format($tax['amount'], 2) }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- Notes --}}
    @if($document->notes)
        <div class="notes-section">
            <h4>{{ __('Notes') }}</h4>
            <p>{{ nl2br($document->notes) }}</p>
        </div>
    @endif

    {{-- Summary --}}
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="summary-label">{{ __('Subtotal') }}</td>
                <td class="summary-value">{{ number_format($document->subtotal, 2) }}</td>
            </tr>
            @if($document->discount_amount > 0)
                <tr>
                    <td class="summary-label">{{ __('Discount') }}</td>
                    <td class="summary-value">-{{ number_format($document->discount_amount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="summary-label">{{ __('Taxes') }}</td>
                <td class="summary-value">{{ number_format($document->tax_amount, 2) }}</td>
            </tr>
            <tr class="summary-total">
                <td class="summary-label">{{ __('Total') }}</td>
                <td class="summary-value">{{ number_format($document->total, 2) }}</td>
            </tr>
        </table>
    </div>
@endsection
