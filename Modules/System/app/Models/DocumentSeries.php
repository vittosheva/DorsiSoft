<?php

declare(strict_types=1);

namespace Modules\System\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Core\Models\Company;
use Modules\Core\Models\Establishment;
use Modules\Core\Models\Traits\HasTenancy;
use Modules\Core\Support\Models\BaseModel;

final class DocumentSeries extends BaseModel
{
    use HasFactory;
    use HasTenancy;
    use Userstamps;

    protected $table = 'sys_document_series';

    protected $fillable = [
        'company_id',
        'document_type_id',
        'establishment_id',
        'prefix',
        'current_sequence',
        'padding',
        'reset_year',
        'auto_reset_yearly',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'current_sequence' => 'integer',
            'padding' => 'integer',
            'reset_year' => 'integer',
            'auto_reset_yearly' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class, 'establishment_id');
    }

    /**
     * Genera el número formateado sin incrementar el contador.
     * Debe llamarse dentro de una transacción con lockForUpdate().
     */
    public function nextFormatted(): string
    {
        $next = $this->current_sequence + 1;

        return $this->format($next);
    }

    /**
     * Incrementa el secuencial y devuelve el número formateado.
     * Debe llamarse dentro de una transacción con lockForUpdate().
     */
    public function increment2(): string // TODO
    {
        if ($this->auto_reset_yearly) {
            $currentYear = (int) now()->format('Y');
            if ($this->reset_year !== $currentYear) {
                $this->current_sequence = 0;
                $this->reset_year = $currentYear;
            }
        }

        $this->current_sequence += 1;
        $this->saveQuietly();

        return $this->format($this->current_sequence);
    }

    private function format(int $sequence): string
    {
        $padded = mb_str_pad((string) $sequence, $this->padding, '0', STR_PAD_LEFT);

        if (filled($this->prefix)) {
            return "{$this->prefix}-{$padded}";
        }

        return $padded;
    }
}
