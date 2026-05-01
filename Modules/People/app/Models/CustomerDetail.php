<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

final class CustomerDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Userstamps;

    protected $table = 'core_customer_details';

    protected $fillable = [
        'business_partner_id',
        'seller_id',
        'seller_name',
        'credit_limit',
        'credit_balance',
        'payment_terms_days',
        'discount_percentage',
        'tax_exempt',
        'rating',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_exempt' => 'boolean',
    ];

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id')->withoutGlobalScopes();
    }
}
