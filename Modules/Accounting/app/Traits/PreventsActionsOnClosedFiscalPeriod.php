<?php

declare(strict_types=1);

namespace Modules\Accounting\Traits;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Models\FiscalPeriod;

trait PreventsActionsOnClosedFiscalPeriod
{
    public static function bootPreventsActionsOnClosedFiscalPeriod()
    {
        static::creating(function (Model $model) {
            if (isset($model->fiscal_period_id)) {
                $period = FiscalPeriod::find($model->fiscal_period_id);

                if ($period && $period->isClosed()) {
                    throw new DomainException(__('Cannot record transactions in a closed fiscal period.'));
                }
            }
        });
        static::updating(function (Model $model) {
            if (isset($model->fiscal_period_id)) {
                $period = FiscalPeriod::find($model->fiscal_period_id);

                if ($period && $period->isClosed()) {
                    throw new DomainException(__('Cannot modify transactions in a closed fiscal period.'));
                }
            }
        });
    }
}
