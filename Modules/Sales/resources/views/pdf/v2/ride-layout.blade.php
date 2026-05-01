<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $document->getRideSriDocumentTypeLabel() }} - {{ $document->getSriSequentialCode() }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 8.5px;
            color: #1a1a1a;
            line-height: 1.4;
            background: #ffffff;
        }

        /* ── HEADER ─────────────────────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-left {
            width: 55%;
            vertical-align: top;
            padding-right: 10px;
        }

        .header-right {
            width: 45%;
            vertical-align: top;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 2px;
            text-transform: uppercase;
        }

        .company-trade-name {
            font-size: 8px;
            color: #555;
            margin-bottom: 2px;
        }

        .company-info {
            font-size: 7.5px;
            color: #444;
            margin-top: 1px;
        }

        .sri-box {
            border: 1px solid #333;
            padding: 5px 7px;
            font-size: 7.5px;
        }

        .sri-box-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .sri-box-row {
            margin-bottom: 2px;
        }

        .sri-box-label {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 7px;
            color: #555;
        }

        .sri-box-value {
            color: #1a1a1a;
        }

        .sri-box-access-key {
            font-family: monospace;
            font-size: 7px;
            word-break: break-all;
            color: #1a1a1a;
            margin-top: 1px;
        }

        /* ── BARCODE ────────────────────────────────────────────────── */
        .barcode-section {
            width: 100%;
            border: 1px solid #ccc;
            padding: 5px 8px;
            margin-bottom: 6px;
            text-align: center;
        }

        .barcode-label {
            font-size: 6.5px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .barcode-display {
            font-family: monospace;
            font-size: 10px;
            letter-spacing: 3px;
            color: #1a1a1a;
            word-break: break-all;
        }

        /* ── CUSTOMER ───────────────────────────────────────────────── */
        .customer-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ccc;
            margin-bottom: 6px;
        }

        .customer-table td {
            padding: 4px 6px;
            border-right: 1px solid #ccc;
            vertical-align: top;
        }

        .customer-table td:last-child {
            border-right: none;
        }

        .field-label {
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 1px;
        }

        .field-value {
            font-size: 8px;
            color: #1a1a1a;
            font-weight: bold;
        }

        /* ── ITEMS TABLE ────────────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .items-table thead tr {
            background-color: #e8e8e8;
        }

        .items-table thead th {
            padding: 4px 5px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            color: #333;
            text-align: left;
            border: 1px solid #bbb;
        }

        .items-table thead th.th-right { text-align: right; }
        .items-table thead th.th-center { text-align: center; }

        .items-table tbody td {
            padding: 4px 5px;
            font-size: 8px;
            color: #1a1a1a;
            border: 1px solid #ccc;
            vertical-align: top;
        }

        .items-table tbody td.td-right { text-align: right; }
        .items-table tbody td.td-center { text-align: center; }

        .items-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* ── BOTTOM TWO-COLUMN ──────────────────────────────────────── */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .additional-info-col {
            width: 52%;
            vertical-align: top;
            padding-right: 8px;
        }

        .totals-col {
            width: 48%;
            vertical-align: top;
        }

        .section-heading {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
            margin-bottom: 4px;
        }

        .additional-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .additional-info-table th {
            background-color: #e8e8e8;
            padding: 3px 5px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #bbb;
            text-align: left;
        }

        .additional-info-table td {
            padding: 3px 5px;
            font-size: 7.5px;
            border: 1px solid #ccc;
            vertical-align: top;
        }

        /* ── TOTALS ─────────────────────────────────────────────────── */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 3px 5px;
            font-size: 8px;
            border: 1px solid #ccc;
        }

        .totals-label {
            font-weight: bold;
            text-transform: uppercase;
            color: #444;
            width: 65%;
        }

        .totals-value {
            text-align: right;
            color: #1a1a1a;
        }

        .totals-grand-label {
            font-size: 9px;
            font-weight: bold;
            background-color: #e8e8e8;
        }

        .totals-grand-value {
            font-size: 9px;
            font-weight: bold;
            background-color: #e8e8e8;
        }

        /* ── PAYMENTS TABLE ─────────────────────────────────────────── */
        .payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .payments-table th {
            background-color: #e8e8e8;
            padding: 4px 5px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #bbb;
            text-align: left;
        }

        .payments-table th.th-right { text-align: right; }

        .payments-table td {
            padding: 4px 5px;
            font-size: 8px;
            border: 1px solid #ccc;
        }

        .payments-table td.td-right { text-align: right; }

        /* ── FOOTER ─────────────────────────────────────────────────── */
        .doc-footer {
            border-top: 1px solid #ccc;
            padding-top: 5px;
            margin-top: 6px;
            font-size: 7px;
            color: #666;
        }

        .brand-footer {
            text-align: center;
            margin-top: 4px;
            font-size: 6.5px;
            color: #888;
        }

        .brand-name {
            color: #cc0000;
            font-weight: bold;
        }
    </style>
</head>
<body>

    {{-- ── HEADER ──────────────────────────────────────────────────────── --}}
    <table class="header-table">
        <tr>
            <td class="header-left">
                <div class="company-name">{{ $document->company?->legal_name }}</div>
                @if($document->company?->trade_name && $document->company->trade_name !== $document->company->legal_name)
                    <div class="company-trade-name">{{ $document->company->trade_name }}</div>
                @endif
                <div class="company-info">RUC: {{ $document->company?->ruc }}</div>
                @if($document->company?->email)
                    <div class="company-info">{{ $document->company->email }}</div>
                @endif
                @if($document->company?->phone)
                    <div class="company-info">Telf: {{ $document->company->phone }}</div>
                @endif
                @if($document->company?->tax_address)
                    <div class="company-info">Dir: {{ $document->company->tax_address }}</div>
                @endif
            </td>
            <td class="header-right">
                <div class="sri-box">
                    <div class="sri-box-title">@yield('ride-document-type-label', 'DOCUMENTO ELECTRÓNICO')</div>
                    <div class="sri-box-row">
                        <span class="sri-box-label">RUC:</span>
                        <span class="sri-box-value">{{ $document->company?->ruc }}</span>
                    </div>
                    <div class="sri-box-row">
                        <span class="sri-box-label">N°:</span>
                        <span class="sri-box-value">{{ $document->getSriSequentialCode() ?? '-' }}</span>
                    </div>
                    <div class="sri-box-row">
                        <span class="sri-box-label">NÚMERO DE AUTORIZACIÓN:</span>
                    </div>
                    <div class="sri-box-access-key">{{ $document->access_key ?? '-' }}</div>
                    <div class="sri-box-row" style="margin-top:3px;">
                        <span class="sri-box-label">FECHA Y HORA DE AUTORIZACIÓN:</span>
                        <span class="sri-box-value">{{ $document->electronic_authorized_at?->format('d/m/Y H:i:s') ?? '-' }}</span>
                    </div>
                    <div class="sri-box-row">
                        <span class="sri-box-label">AMBIENTE:</span>
                        <span class="sri-box-value">{{ $document->company?->sri_environment?->getLabel() ?? '-' }}</span>
                    </div>
                    <div class="sri-box-row">
                        <span class="sri-box-label">EMISIÓN:</span>
                        <span class="sri-box-value">Normal</span>
                    </div>
                    <div class="sri-box-row">
                        <span class="sri-box-label">OBLIGADO A LLEVAR CONTABILIDAD:</span>
                        <span class="sri-box-value">{{ ($document->company?->is_accounting_required ?? false) ? 'SI' : 'NO' }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- ── BARCODE SECTION ─────────────────────────────────────────────── --}}
    @if($document->access_key)
        @php
            $barcodePng = (new \Picqer\Barcode\BarcodeGeneratorPNG())
                ->getBarcode($document->access_key, \Picqer\Barcode\BarcodeGeneratorPNG::TYPE_CODE_128, 2, 60);
            $barcodeUri = 'data:image/png;base64,' . base64_encode($barcodePng);
        @endphp
        <div class="barcode-section">
            <div class="barcode-label">Clave de Acceso / Número de Autorización</div>
            <img src="{{ $barcodeUri }}" style="width:100%; height:45px;" alt="{{ $document->access_key }}">
            <div class="barcode-display" style="font-size:6.5px; letter-spacing:1px;">{{ $document->access_key }}</div>
        </div>
    @endif

    {{-- ── CUSTOMER SECTION ────────────────────────────────────────────── --}}
    <table class="customer-table">
        <tr>
            <td style="width:28%;">
                <div class="field-label">RUC / Cédula</div>
                <div class="field-value">{{ $document->customer_identification ?? '-' }}</div>
            </td>
            <td style="width:50%;">
                <div class="field-label">Razón Social / Nombre</div>
                <div class="field-value">{{ $document->customer_name ?? '-' }}</div>
            </td>
            <td style="width:22%;">
                <div class="field-label">Fecha Emisión</div>
                <div class="field-value">{{ $document->issue_date?->format('d/m/Y') ?? '-' }}</div>
            </td>
        </tr>
    </table>

    {{-- ── DOCUMENT-SPECIFIC ITEMS ─────────────────────────────────────── --}}
    @yield('ride-items')

    {{-- ── BOTTOM: ADDITIONAL INFO + TOTALS ───────────────────────────── --}}
    <table class="bottom-table">
        <tr>
            <td class="additional-info-col">
                @if(!empty($document->additional_info))
                    <table class="additional-info-table">
                        <thead>
                            <tr>
                                <th colspan="2">Información Adicional</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($document->additional_info as $info)
                                <tr>
                                    <td style="width:40%; font-weight:bold;">{{ $info['nombre'] ?? '' }}</td>
                                    <td>{{ $info['valor'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </td>
            <td class="totals-col">
                @yield('ride-summary')
            </td>
        </tr>
    </table>

    {{-- ── PAYMENT METHODS ─────────────────────────────────────────────── --}}
    @if(!empty($document->sri_payments))
        <table class="payments-table">
            <thead>
                <tr>
                    <th>Forma de Pago</th>
                    <th class="th-right">Valor</th>
                    <th class="th-right">Plazo</th>
                    <th>Unidad de Tiempo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($document->sri_payments as $payment)
                    <tr>
                        <td>{{ \Modules\Sales\Enums\SriPaymentMethodEnum::tryFrom($payment['formaPago'] ?? '')?->getLabel() ?? ($payment['formaPago'] ?? '-') }}</td>
                        <td class="td-right">{{ number_format((float) ($payment['total'] ?? 0), 2) }}</td>
                        <td class="td-right">{{ $payment['plazo'] ?? '0' }}</td>
                        <td>{{ $payment['unidadTiempo'] ?? 'dias' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- ── FOOTER ──────────────────────────────────────────────────────── --}}
    <div class="doc-footer">
        Emitido por: {{ $document->creator?->name }} &mdash; {{ $document->company?->legal_name }}
    </div>
    <div class="brand-footer">
        UpConta es una marca registrada de <span class="brand-name">TOC Systems</span> &mdash; Todos los derechos reservados &copy;{{ date('Y') }}
    </div>

</body>
</html>
