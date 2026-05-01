@extends('sales::pdf.layouts.dorsi')

@section('title', __('Debit Note') . ' ' . $document->code)
@section('doc-type', __('Debit Note'))

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
    @php($pdfDateFormatter = \Modules\Core\Support\Pdf\PdfDateFormatter::class)
    @php($payments = $document->getResolvedSriPayments())
    @php($paymentMethods = collect($payments)->pluck('method')->filter()->join(', '))
    @php($paymentsTotal = collect($payments)->sum(static fn (array $payment): float => (float) ($payment['amount'] ?? 0)))

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
                    @elseif($document->ext_invoice_code)
                        <tr>
                            <td class="detail-key">{{ __('Invoice') }}</td>
                            <td class="detail-val">{{ $document->ext_invoice_code }}</td>
                        </tr>
                    @endif
                    {{-- @include('sales::pdf.partials.sri-detail-rows', ['document' => $document]) --}}
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="th-center">#</th>
                <th>{{ __('Reason') }}</th>
                <th style="width:18%;" class="th-right">{{ __('Value') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($document->reasons ?? []) as $reason)
                <tr @if($loop->even) class="row-alt" @endif>
                    <td class="td-center">{{ $loop->iteration }}</td>
                    <td>{{ $reason['reason'] ?? '—' }}</td>
                    <td class="td-right">{{ number_format((float) ($reason['value'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="bottom-table">
        <tr>
            <td class="notes-cell">
                @if($document->notes)
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
                    @foreach($taxBreakdown ?? [] as $taxRow)
                        <tr>
                            <td class="totals-row-label">{{ $taxRow['label'] }}</td>
                            <td class="totals-row-value">{{ number_format((float) $taxRow['tax_amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    @if(($taxBreakdown ?? []) === [] && $document->tax_amount > 0)
                        <tr>
                            <td class="totals-row-label">{{ __('Tax') }}</td>
                            <td class="totals-row-value">{{ number_format((float) $document->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="totals-grand-label">{{ __('Total') }} {{ $document->currency_code ?? 'USD' }}</td>
                        <td class="totals-grand-value">{{ number_format((float) $document->total, 2) }}</td>
                    </tr>
                    @if($paymentsTotal > 0)
                        <tr>
                            <td class="totals-paid-label">{{ __('Payment amount') }}</td>
                            <td class="totals-paid-value">{{ number_format($paymentsTotal, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

@endsection