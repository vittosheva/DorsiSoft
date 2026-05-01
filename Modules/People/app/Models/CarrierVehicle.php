<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class CarrierVehicle extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_carrier_vehicles';

    protected $fillable = [
        'business_partner_id',
        'driver_name',
        'driver_identification',
        'driver_license',
        'driver_license_type',
        'driver_license_expiry_date',
        'vehicle_plate',
        'vehicle_type',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_year',
        'vehicle_capacity_tons',
        'vehicle_capacity_m3',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'driver_license_expiry_date' => 'date',
        'vehicle_capacity_tons' => 'decimal:2',
        'vehicle_capacity_m3' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }
}
