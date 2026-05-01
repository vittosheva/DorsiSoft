<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class SupplierDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_supplier_details';

    protected $fillable = [
        'business_partner_id',
        'payment_terms_days',
        'lead_time_days',
        'tax_withholding_applicable',
        'rating',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'tax_withholding_applicable' => 'boolean',
    ];

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }
}
