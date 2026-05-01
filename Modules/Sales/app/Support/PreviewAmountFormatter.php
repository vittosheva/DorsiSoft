<?php

declare(strict_types=1);

namespace Modules\Sales\Support;

use Illuminate\Database\Eloquent\Model;

final class PreviewAmountFormatter
{
    public static function normalize(Model $record, array $amountAttributes): Model
    {
        $previewRecord = clone $record;
        $previewRecord->mergeCasts(array_fill_keys($amountAttributes, 'float'));

        foreach ($amountAttributes as $attribute) {
            $rawValue = $record->getAttribute($attribute);

            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            $previewRecord->setAttribute($attribute, round((float) $rawValue, 2));
        }

        return $previewRecord;
    }
}
