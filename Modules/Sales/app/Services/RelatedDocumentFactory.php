<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Modules\Sales\Models\CreditNote;
use Modules\Sales\Models\DebitNote;
use Modules\Sales\Models\Invoice;

final class RelatedDocumentFactory
{
    /**
     * Crea una Nota de Crédito prellenada desde una factura.
     * Permite overrides para casos de nota parcial.
     */
    public static function creditNoteFromInvoice(Invoice $invoice, array $overrides = []): CreditNote
    {
        $data = [
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'business_partner_id' => $invoice->business_partner_id,
            'customer_name' => $invoice->customer_name,
            'customer_trade_name' => $invoice->customer_trade_name,
            'customer_identification_type' => $invoice->customer_identification_type,
            'customer_identification' => $invoice->customer_identification,
            'customer_address' => $invoice->customer_address,
            'customer_email' => $invoice->customer_email,
            'customer_phone' => $invoice->customer_phone,
            'currency_code' => $invoice->currency_code,
            'subtotal' => $invoice->subtotal,
            'tax_amount' => $invoice->tax_amount,
            'total' => $invoice->total,
            'issue_date' => now(),
            'notes' => $invoice->notes,
            // ...otros campos relevantes
        ];
        $data = array_merge($data, $overrides);
        $creditNote = new CreditNote($data);

        // Copiar detalles/items si aplica (ejemplo, para nota parcial, filtrar items)
        // $creditNote->items = ...
        return $creditNote;
    }

    /**
     * Crea una Nota de Débito prellenada desde una factura.
     * Permite overrides para casos de ajuste.
     */
    public static function debitNoteFromInvoice(Invoice $invoice, array $overrides = []): DebitNote
    {
        $data = [
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'business_partner_id' => $invoice->business_partner_id,
            'customer_name' => $invoice->customer_name,
            'customer_trade_name' => $invoice->customer_trade_name,
            'customer_identification_type' => $invoice->customer_identification_type,
            'customer_identification' => $invoice->customer_identification,
            'customer_address' => $invoice->customer_address,
            'customer_email' => $invoice->customer_email,
            'customer_phone' => $invoice->customer_phone,
            'currency_code' => $invoice->currency_code,
            'subtotal' => $invoice->subtotal,
            'tax_amount' => $invoice->tax_amount,
            'total' => $invoice->total,
            'issue_date' => now(),
            'notes' => $invoice->notes,
            // ...otros campos relevantes
        ];
        $data = array_merge($data, $overrides);
        $debitNote = new DebitNote($data);

        // Copiar detalles/items si aplica
        // $debitNote->items = ...
        return $debitNote;
    }
}
