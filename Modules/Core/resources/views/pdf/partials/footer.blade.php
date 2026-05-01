<table style="width: 100%; border-collapse: collapse; table-layout: fixed; font-family: DejaVu Sans, Arial, sans-serif; font-size: 7.5px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 5px; box-sizing: border-box;">
    <tr>
        <td style="text-align: left; color: #94a3b8; font-size: 7.5px; padding: 4px 10mm 0 10mm; vertical-align: top;">
            {{ $document->company?->legal_name }}
            @if($document->company?->tax_address)
                &mdash; {{ $document->company->tax_address }}
            @endif
        </td>
        <td style="text-align: right; color: #94a3b8; font-size: 7.5px; padding: 4px 10mm 0 10mm; vertical-align: top;">
            {{ __('Generated on') }}: {{ \Modules\Core\Support\Pdf\PdfDateFormatter::formatDateTime(now()) }}
        </td>
    </tr>
</table>
