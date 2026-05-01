<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CreditNoteApplication extends Model
{
    protected $table = 'sales_credit_note_applications';

    protected $fillable = [
        'credit_note_id',
        'invoice_id',
        'amount',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'applied_at' => 'datetime',
        ];
    }

    /**
     * Nota de crédito que originó esta aplicación.
     */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class, 'credit_note_id');
    }

    /**
     * Factura destino que recibe el crédito. NO es la factura original de la NC.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
