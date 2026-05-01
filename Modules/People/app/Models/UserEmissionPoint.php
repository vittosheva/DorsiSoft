<?php

declare(strict_types=1);

namespace Modules\People\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Core\Models\CashRegister;
use Modules\Core\Models\EmissionPoint;
use Modules\Core\Models\PaymentMethod;

final class UserEmissionPoint extends Pivot
{
    public $incrementing = true;

    protected $table = 'user_emission_points';

    protected $fillable = [
        'user_id',
        'emission_point_id',
        'is_default',
        'payment_method_id',
        'cash_register_id',
        'allow_mixed_payments',
        'restrict_payment_methods',
        'require_shift',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'allow_mixed_payments' => 'boolean',
            'restrict_payment_methods' => 'boolean',
            'require_shift' => 'boolean',
        ];
    }

    public function emissionPoint(): BelongsTo
    {
        return $this->belongsTo(EmissionPoint::class, 'emission_point_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    protected static function booted(): void
    {
        self::saving(function (self $pivot) {
            if ($pivot->is_default && $pivot->exists) {
                self::where('user_id', $pivot->user_id)
                    ->where('emission_point_id', '!=', $pivot->emission_point_id)
                    ->update(['is_default' => false]);
            }
        });

        self::created(function (self $pivot) {
            if ($pivot->is_default) {
                self::where('user_id', $pivot->user_id)
                    ->where('emission_point_id', '!=', $pivot->emission_point_id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
