<?php

declare(strict_types=1);

namespace Modules\Sales\Models\Traits;

use Modules\System\Models\DocumentType;

trait AutoAssignsDocumentType
{
    private static array $documentTypeIdCache = [];

    public static function bootAutoAssignsDocumentType(): void
    {
        static::creating(static function (self $model): void {
            self::ensureDocumentType($model);
        });

        static::updating(static function (self $model): void {
            self::ensureDocumentType($model);
        });

        /* static::retrieved(static function (self $model): void {
            self::ensureDocumentType($model);
        }); */
    }

    private static function ensureDocumentType(self $model): void
    {
        if (filled($model->document_type_id) || blank($model->company_id)) {
            return;
        }

        $enum = $model->getSriDocumentType();
        $cacheKey = "{$model->company_id}:{$enum->value}";

        if (! array_key_exists($cacheKey, self::$documentTypeIdCache)) {
            self::$documentTypeIdCache[$cacheKey] = DocumentType::query()
                ->where('company_id', $model->company_id)
                ->where('code', $enum->value)
                ->active()
                ->value('id');
        }

        $model->document_type_id = self::$documentTypeIdCache[$cacheKey];
    }
}
