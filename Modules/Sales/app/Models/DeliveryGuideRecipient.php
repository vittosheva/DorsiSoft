<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\People\Models\BusinessPartner;
use Modules\Sales\Enums\DeliveryGuideTransferReasonEnum;

final class DeliveryGuideRecipient extends Model
{
    protected $table = 'sales_delivery_guide_recipients';

    protected $fillable = [
        'delivery_guide_id',
        'business_partner_id',
        'recipient_name',
        'recipient_identification_type',
        'recipient_identification',
        'destination_address',
        'transfer_reason',
        'route',
        'customs_doc',
        'destination_establishment_code',
        'invoice_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'transfer_reason' => DeliveryGuideTransferReasonEnum::class,
            'sort_order' => 'integer',
        ];
    }

    public function deliveryGuide(): BelongsTo
    {
        return $this->belongsTo(DeliveryGuide::class, 'delivery_guide_id');
    }

    public function businessPartner(): BelongsTo
    {
        return $this->belongsTo(BusinessPartner::class, 'business_partner_id')->withoutGlobalScopes();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id')->withoutGlobalScopes();
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryGuideItem::class, 'delivery_guide_recipient_id')->orderBy('sort_order');
    }
}
