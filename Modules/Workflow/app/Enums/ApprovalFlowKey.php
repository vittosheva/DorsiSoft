<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

enum ApprovalFlowKey: string
{
    case InvoiceIssuance = 'invoice_issuance';
    case CreditNoteIssuance = 'credit_note_issuance';
    case SalesOrderConfirmation = 'sales_order_confirmation';
    case Authorization = 'authorization';
    case RefundApproval = 'refund_approval';
    case PurchaseIssuance = 'purchase_issuance';
    case PurchaseApproval = 'purchase_approval';
    case ExpenseApproval = 'expense_approval';
    case ExpenseReimbursement = 'expense_reimbursement';
    case InventoryAdjustment = 'inventory_adjustment';
    case StockTransfer = 'stock_transfer';
    case WithholdingRelease = 'withholding_release';
    case SettlementApproval = 'settlement_approval';
    case GeneralApproval = 'general_approval';
}
