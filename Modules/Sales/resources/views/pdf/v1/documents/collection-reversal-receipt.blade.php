@extends('sales::pdf.layouts.dorsi')

@section('title', __('Reversal receipt') . ' #' . $document->getKey())
@section('doc-type', __('Reversal'))

@section('content')
    @php($pdfDateFormatter = \Modules\Core\Support\Pdf\PdfDateFormatter::class)

    {{-- Collection & Invoice Info --}}
    <table class="billing-table">
        <tr>
            <td class="billing-left">
                <div class="section-title">{{ __('Collection') }}</div>
                <table class="detail-table">
                    <tr>
                        <td class="detail-key">{{ __('Code') }}</td>
                        <td class="detail-val">{{ $document->collection?->code }}</td>
                    </tr>
                    <tr>
                        <td class="detail-key">{{ __('Date') }}</td>
                        <td class="detail-val">{{ $formattedCollectionDate ?? $pdfDateFormatter::formatDate($document->collection?->collection_date) }}</td>
                    </tr>
                    <tr>
                        <td class="detail-key">{{ __('Customer') }}</td>
                        <td class="detail-val">{{ $document->collection?->customer_name }}</td>
                    </tr>
                    <tr>
                        <td class="detail-key">{{ __('Method') }}</td>
                        <td class="detail-val">{{ $document->collection?->collection_method?->getLabel() }}</td>
                    </tr>
                    @if($document->collection?->reference_number)
                        <tr>
                            <td class="detail-key">{{ __('Reference') }}</td>
                            <td class="detail-val">{{ $document->collection->reference_number }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td class="billing-right">
                <div class="section-title">{{ __('Invoice') }}</div>
                <table class="detail-table">
                    <tr>
                        <td class="detail-key">{{ __('Code') }}</td>
                        <td class="detail-val">{{ $document->invoice?->code }}</td>
                    </tr>
                    @if($document->invoice?->issue_date)
                        <tr>
                            <td class="detail-key">{{ __('Date') }}</td>
                            <td class="detail-val">{{ $formattedInvoiceDate ?? $pdfDateFormatter::formatDate($document->invoice->issue_date) }}</td>
                        </tr>
                    @endif
                    @if($document->invoice?->total)
                        <tr>
                            <td class="detail-key">{{ __('Total') }}</td>
                            <td class="detail-val">{{ number_format((float) $document->invoice->total, 2) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="detail-key">{{ __('Reversal Date') }}</td>
                        <td class="detail-val">{{ $formattedReversedAt ?? $pdfDateFormatter::formatDateTime($document->reversed_at) }}</td>
                    </tr>
                    @if($document->reversedBy)
                        <tr>
                            <td class="detail-key">{{ __('Reversed By') }}</td>
                            <td class="detail-val">{{ $document->reversedBy->name }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Reason --}}
    <div class="info-block">
        <div class="info-block-title">{{ __('Reason for Reversal') }}</div>
        <div class="info-block-reason">{{ $document->reason }}</div>
    </div>

    {{-- Amount --}}
    <div class="amount-block">
        <div class="amount-label">{{ __('Amount Reversed') }}</div>
        <div class="amount-value">{{ number_format((float) $document->reversed_amount, 2) }}</div>
    </div>

    <div class="footer-rule"></div>
    <div class="footer-text">
        {{ __('This document is an internal record of a collection allocation reversal.') }}
        &mdash; {{ $formattedGeneratedAt ?? $pdfDateFormatter::formatDateTime(now()) }}
    </div>

@endsection
