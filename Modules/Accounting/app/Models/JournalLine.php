<?php

declare(strict_types=1);

namespace Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Support\Models\BaseModel;

final class JournalLine extends BaseModel
{
    use HasFactory;

    protected $table = 'fin_journal_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'description',
        'debit',
        'credit',
        'currency_code',
        'exchange_rate',
        'debit_base',
        'credit_base',
        'line_number',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'decimal:4',
            'credit' => 'decimal:4',
            'exchange_rate' => 'decimal:6',
            'debit_base' => 'decimal:4',
            'credit_base' => 'decimal:4',
            'line_number' => 'integer',
        ];
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Monto neto de la línea: positivo = débito, negativo = crédito.
     */
    public function netAmount(): string
    {
        return bcsub((string) $this->debit, (string) $this->credit, 4);
    }
}
