<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5px;
            color: #0f172a;
            line-height: 1.1;
            background: #ffffff;
            margin: 10mm 10mm 10mm 10mm;
        }

        /* ─────────────────────────────────────────
           TOP RULE
        ───────────────────────────────────────── */
        .top-rule {
            height: 2px;
            background-color: #999999;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* ─────────────────────────────────────────
           HEADER — Company left | Document right
        ───────────────────────────────────────── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0px;
            padding-bottom: 0px;
            /* border-bottom: 1px solid #cbd5e1; */
        }

        .header-company {
            width: 50%;
            vertical-align: top;
        }

        .header-company img {
            max-width: 130px;
            max-height: 50px;
            margin-bottom: 10px;
            display: block;
        }

        .company-legal-name {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .company-trade-name {
            font-size: 8.5px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .company-info {
            font-size: 8.5px;
            color: #64748b;
            margin-top: 1px;
        }

        .header-document {
            width: 50%;
            vertical-align: top;
            text-align: left;
        }

        .doc-type-label {
            font-size: 24px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: -1px;
            line-height: 1;
        }

        .doc-number-label {
            font-size: 11px;
            color: #2563eb;
            font-weight: bold;
            margin-top: 6px;
            letter-spacing: 0.5px;
        }

        .doc-status-wrap {
            margin-top: 7px;
        }

        /* ─────────────────────────────────────────
           STATUS BADGES — subtle, outlined
        ───────────────────────────────────────── */
        .status-badge {
            display: inline-block;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 2px 7px;
            border-radius: 3px;
            border: 1px solid;
        }

        .status-draft     { color: #64748b; border-color: #cbd5e1; background-color: #f8fafc; }
        .status-issued    { color: #16a34a; border-color: #86efac; background-color: #f0fdf4; }
        .status-paid      { color: #2563eb; border-color: #93c5fd; background-color: #eff6ff; }
        .status-voided    { color: #dc2626; border-color: #fca5a5; background-color: #fef2f2; }
        .status-sent      { color: #0891b2; border-color: #67e8f9; background-color: #ecfeff; }
        .status-accepted  { color: #16a34a; border-color: #86efac; background-color: #f0fdf4; }
        .status-rejected  { color: #dc2626; border-color: #fca5a5; background-color: #fef2f2; }
        .status-expired   { color: #b45309; border-color: #fcd34d; background-color: #fffbeb; }
        .status-pending   { color: #b45309; border-color: #fcd34d; background-color: #fffbeb; }
        .status-confirmed { color: #16a34a; border-color: #86efac; background-color: #f0fdf4; }
        .status-cancelled { color: #dc2626; border-color: #fca5a5; background-color: #fef2f2; }
        .status-completed { color: #2563eb; border-color: #93c5fd; background-color: #eff6ff; }

        /* ─────────────────────────────────────────
           BILLING SECTION — Customer | Details
        ───────────────────────────────────────── */
        .billing-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 26px;
        }

        .billing-left {
            width: 50%;
            vertical-align: top;
            padding-right: 28px;
        }

        .billing-right {
            width: 50%;
            vertical-align: top;
        }

        .section-title {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .customer-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .customer-detail {
            font-size: 8.5px;
            color: #475569;
            margin-top: 2px;
        }

        /* ─────────────────────────────────────────
           DETAILS TABLE (key-value pairs)
        ───────────────────────────────────────── */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table td {
            padding: 3px 0;
            font-size: 9px;
            vertical-align: top;
            border: none;
        }

        .detail-key {
            width: 30%;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            padding-right: 8px;
        }

        .detail-val {
            color: #0f172a;
            font-weight: bold;
        }

        /* ─────────────────────────────────────────
           ITEMS TABLE
        ───────────────────────────────────────── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .items-table thead tr {
            background-color: #f1f5f9;
        }

        .items-table thead th {
            padding: 7px 9px;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.5px;
            text-align: left;
            border-top: 1px solid #cbd5e1;
            border-bottom: 1px solid #cbd5e1;
        }

        .items-table thead th.th-right  { text-align: right; }
        .items-table thead th.th-center { text-align: center; }

        .items-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table tbody tr.row-alt {
            background-color: #f8fafc;
        }

        .items-table tbody tr:last-child {
            border-bottom: 2px solid #cbd5e1;
        }

        .items-table tbody td {
            padding: 9px 9px;
            font-size: 9.5px;
            color: #1e293b;
            vertical-align: top;
        }

        .items-table tbody td.td-right  { text-align: right; }
        .items-table tbody td.td-center { text-align: center; }

        .item-code {
            font-size: 8.5px;
            color: #64748b;
        }

        .item-description {
            font-size: 8px;
            color: #94a3b8;
            margin-top: 2px;
        }

        .nowrap {
            white-space: nowrap;
        }

        .em-dash {
            color: #cbd5e1;
        }

        /* ─────────────────────────────────────────
           TOTALS + NOTES
        ───────────────────────────────────────── */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }

        .notes-cell {
            width: 52%;
            vertical-align: top;
            padding-right: 24px;
        }

        .totals-cell {
            width: 48%;
            vertical-align: top;
        }

        .notes-title {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            padding-bottom: 5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .notes-body {
            font-size: 8.5px;
            color: #475569;
            line-height: 1.6;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 4px 0;
            font-size: 9.5px;
        }

        .totals-row-label {
            color: #475569;
            text-align: left;
        }

        .totals-row-value {
            text-align: right;
            color: #1e293b;
        }

        .totals-discount-label { color: #dc2626; }
        .totals-discount-value { color: #dc2626; text-align: right; }

        .totals-paid-label { color: #16a34a; }
        .totals-paid-value { color: #16a34a; text-align: right; }

        .totals-due-label  { color: #b45309; font-weight: bold; }
        .totals-due-value  { color: #b45309; font-weight: bold; text-align: right; }

        .totals-grand-label {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            text-align: left;
            padding-top: 10px;
            border-top: 2px solid #0f172a;
        }

        .totals-grand-value {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            text-align: right;
            padding-top: 10px;
            border-top: 2px solid #0f172a;
        }

        /* ─────────────────────────────────────────
           FOOTER
        ───────────────────────────────────────── */

    </style>
</head>
<body>
    @php($pdfDateFormatter = \Modules\Core\Support\Pdf\PdfDateFormatter::class)
    
    {{-- ─── Header ─── --}}
    <div class="top-rule"></div>
    <table class="header-table">
        <tr>
            <td class="header-company">
                @hasSection('logo-company')
                    @yield('logo-company')
                @endif
                <div class="company-legal-name">{{ $document->company?->legal_name }}</div>
                {{-- @if($document->company?->trade_name && $document->company->trade_name !== $document->company->legal_name)
                    <div class="company-trade-name">{{ $document->company->trade_name }}</div>
                @endif --}}
                <div class="company-info">{{ $document->company?->ruc }}</div>
                @if($document->company?->phone)
                    <div class="company-info">{{ $document->company->phone }}</div>
                @endif
                @if($document->company?->tax_address)
                    <div class="company-info">{{ $document->company->tax_address }}</div>
                @endif
            </td>
            <td class="header-document">
                <div class="doc-type-label">@yield('doc-type') <span class="doc-status-wrap">@yield('doc-status')</span></div>
                @if(method_exists($document, 'getSriSequentialCode') && $document->getSriSequentialCode())
                    <div class="doc-number-label">
                        {{ $document->getSriSequentialCode() }}
                    </div>
                @endif
                {{-- <div class="doc-number-label">{{ $document->code }}</div> --}}
                @hasSection('doc-number-area')
                    @yield('doc-number-area')
                @endif
            </td>
        </tr>
    </table>
    <div class="top-rule"></div>

    {{-- ─── Document-specific content ─── --}}
    @yield('content')
</body>
</html>
