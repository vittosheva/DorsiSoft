@extends('sales::pdf.layouts.dorsi')

@section('title', __('Withholding') . ' ' . $document->code)
@section('doc-type', __('Withholding'))

@section('doc-status')
    <div class="status-badge status-{{ $document->status->value }}">{{ $document->status->getLabel() }}</div>
@endsection

@section('logo-company')
    @include('sales::pdf.partials.sri-logo-company', ['document' => $document->company])
@endsection

@section('doc-number-area')
    @include('sales::pdf.partials.sri-barcode', ['document' => $document])
@endsection

@section('content')
    @php($pdfDateFormatter = \Modules\Core\Support\Pdf\PdfDateFormatter::class)

    {{-- Billing Section --}}
    <table class="billing-table">
        <tr>
            <td class="billing-left">
                <div class="customer-name">{{ $document->supplier_name }}</div>
                @if($document->supplier_identification)
                    <div class="customer-detail">{{ $document->supplier_identification }}</div>
                @endif
                @if($document->supplier_address)
                    <div class="customer-detail">{{ $document->supplier_address }}</div>
                @endif
            </td>
            <td class="billing-right">
                <table class="detail-table">
                    <tr>
                        <td class="detail-key">{{ __('Issue date') }}</td>
                        <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->issue_date) }}</td>
                    </tr>
                    @if($document->period_fiscal)
                        <tr>
                            <td class="detail-key">{{ __('Fiscal Period') }}</td>
                            <td class="detail-val">{{ $document->period_fiscal }}</td>
                        </tr>
                    @endif
                    @if($document->source_document_type && $document->source_document_number)
                        <tr>
                            <td class="detail-key">{{ __('Source Document') }}</td>
                            <td class="detail-val">{{ __($document->source_document_type) }} {{ $document->source_document_number }}</td>
                        </tr>
                    @endif
                    @if($document->source_document_date)
                        <tr>
                            <td class="detail-key">{{ __('Source Date') }}</td>
                            <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->source_document_date) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;" class="th-center">#</th>
                <th style="width:15%;">{{ __('Tax type') }}</th>
                <th style="width:12%;" class="th-center">{{ __('Tax code') }}</th>
                <th style="width:13%;" class="th-right">{{ __('Tax rate') }} %</th>
                <th style="width:15%;" class="th-right">{{ __('Source Document') }}</th>
                <th style="width:15%;" class="th-right">{{ __('Source Date') }}</th>
                <th style="width:12%;" class="th-right">{{ __('Base amount') }}</th>
                <th style="width:13%;" class="th-right">{{ __('Withheld') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($document->items as $item)
                <tr @if($loop->even) class="row-alt" @endif>
                    <td class="td-center">{{ $loop->iteration }}</td>
                    <td>{{ $item->tax_type }}</td>
                    <td class="td-center">{{ $item->tax_code }}</td>
                    <td class="td-right">{{ number_format((float) $item->tax_rate, 2) }}</td>
                    <td class="td-right">{{ $item->source_document_number ?? '—' }}</td>
                    <td class="td-right">
                        @if($item->source_document_date)
                            {{ $pdfDateFormatter::formatDate($item->source_document_date) }}
                        @else
                            <span class="em-dash">&mdash;</span>
                        @endif
                    </td>
                    <td class="td-right">{{ number_format((float) $item->base_amount, 2) }}</td>
                    <td class="td-right">{{ number_format((float) $item->withheld_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals + Notes --}}
    <table class="bottom-table">
        <tr>
            <td class="notes-cell">
                @if(!empty($document->notes) && $document->notes != '<p></p>')
                    <div class="notes-title">{{ __('Notes') }}</div>
                    <div class="notes-body">{!! $document->notes !!}</div>
                @endif
                @if(!empty($document->additional_info))
                    <div class="notes-title" style="margin-top:8pt;">{{ __('Additional information') }}</div>
                    @foreach($document->additional_info as $info)
                        <div class="notes-body"><strong>{{ $info['key'] ?? '' }}</strong>: {{ $info['value'] ?? '' }}</div>
                    @endforeach
                @endif
            </td>
            <td class="totals-cell">
                <table class="totals-table">
                    <tr>
                        <td class="totals-grand-label">{{ __('Total withheld') }}</td>
                        <td class="totals-grand-value">{{ number_format((float) $document->totalWithheld, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

@endsection
