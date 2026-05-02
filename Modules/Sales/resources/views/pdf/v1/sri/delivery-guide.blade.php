@extends('sales::pdf.layouts.dorsi')

@section('title', __('Delivery Guide') . ' ' . $document->code)
@section('doc-type', __('Delivery Guide'))

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

    {{-- Carrier + Transport Section --}}
    <table class="billing-table">
        <tr>
            <td class="billing-left">
                <div class="customer-name">{{ $document->carrier_name }}</div>
                @if($document->carrier_identification)
                    <div class="customer-detail">{{ $document->carrier_identification }}</div>
                @endif
                @if($document->carrier_plate)
                    <div class="customer-detail">{{ __('Plate') }}: {{ $document->carrier_plate }}</div>
                @endif
                @if($document->carrier_driver_name)
                    <div class="customer-detail">{{ __('Driver') }}: {{ $document->carrier_driver_name }}</div>
                @endif
            </td>
            <td class="billing-right">
                <table class="detail-table">
                    <tr>
                        <td class="detail-key">{{ __('Issue date') }}</td>
                        <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->issue_date) }}</td>
                    </tr>
                    @if($document->transport_start_date)
                        <tr>
                            <td class="detail-key">{{ __('Transport start date') }}</td>
                            <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->transport_start_date) }}</td>
                        </tr>
                    @endif
                    @if($document->transport_end_date)
                        <tr>
                            <td class="detail-key">{{ __('Transport end date') }}</td>
                            <td class="detail-val">{{ $pdfDateFormatter::formatDate($document->transport_end_date) }}</td>
                        </tr>
                    @endif
                    @if($document->origin_address)
                        <tr>
                            <td class="detail-key">{{ __('Origin') }}</td>
                            <td class="detail-val">{{ $document->origin_address }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Recipients --}}
    @foreach($document->recipients as $recipient)
        <div style="margin-top:8pt; font-size:8pt; border-bottom:1px solid #ccc; padding-bottom:2pt;">
            @if($recipient->recipient_identification)
                &mdash; <span style="font-weight: bold;">{{ __('Recipient') }}:</span> {{ $recipient->recipient_name }}{{ $recipient->recipient_identification }}
            @endif
            @if($recipient->destination_address)
                <br>&mdash; <span style="font-weight: bold;">{{ __('Destination address') }}:</span> {{ $recipient->destination_address }}
            @endif
            @if($recipient->transfer_reason)
                <br>&mdash; <span style="font-weight: bold;">{{ __('Reason') }}:</span> {{ $recipient->transfer_reason->getLabel() }}
            @endif
            @if($recipient->route)
                <br>&mdash; <span style="font-weight: bold;">{{ __('Route') }}:</span> {{ $recipient->route }}
            @endif
        </div>

        <table class="items-table" style="margin-top:4pt;">
            <thead>
                <tr>
                    <th style="width:5%;" class="th-center">#</th>
                    <th style="width:12%;">{{ __('Code') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th style="width:10%;" class="th-center">{{ __('Qty') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recipient->items as $item)
                    <tr @if($loop->even) class="row-alt" @endif>
                        <td class="td-center">{{ $loop->iteration }}</td>
                        <td><span class="item-code">{{ $item->product_code }}</span></td>
                        <td>
                            {{ $item->product_name }}
                            @if(filled($item->description) && trim((string) $item->description) !== trim((string) $item->product_name))
                                <div class="item-description">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td class="td-center">{{ number_format((float) $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    {{-- Notes / Additional info --}}
    @if((!empty($document->notes) && $document->notes != '<p></p>') || !empty($document->additional_info))
        <table class="bottom-table" style="margin-top:8pt;">
            <tr>
                <td class="notes-cell" style="width:100%;">
                    @if(!empty($document->notes) && $document->notes != '<p></p>')
                        <div class="notes-title">{{ __('Notes') }}</div>
                        <div class="notes-body">{!! $document->notes !!}</div>
                    @endif
                    @if(!empty($document->additional_info))
                        <div class="notes-title" style="margin-top:8pt;">{{ __('Additional Information') }}</div>
                        @foreach($document->additional_info as $info)
                            <div class="notes-body"><strong>{{ $info['key'] ?? '' }}</strong>: {{ $info['value'] ?? '' }}</div>
                        @endforeach
                    @endif
                </td>
            </tr>
        </table>
    @endif

@endsection
