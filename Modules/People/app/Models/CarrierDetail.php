<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class CarrierDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_carrier_details';

    protected $fillable = [
        'business_partner_id',
        'transport_authorization',
        'authorization_expiry_date',
        'soat_number',
        'soat_expiry_date',
        'cargo_insurance_number',
        'cargo_insurance_expiry_date',
        'insurance_company',
        'insurance_coverage_amount',
        'rating',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'authorization_expiry_date' => 'date',
        'soat_expiry_date' => 'date',
        'cargo_insurance_expiry_date' => 'date',
        'insurance_coverage_amount' => 'decimal:2',
    ];

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }
}
