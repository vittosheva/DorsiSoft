<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Enums\FileTypeEnum;
use Throwable;

final class FileStoragePathService
{
    private static array $customConfigurations = [];

    /**
     * Get the storage path for a specific file type
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @param  Model|null  $model  Optional model instance for context
     * @param  string|null  $tenantId  Tenant ID (defaults to current tenant)
     * @param  array<string, mixed>  $context  Additional context variables
     * @return string The computed storage path
     */
    public static function getPath(
        FileTypeEnum $fileType,
        ?Model $model = null,
        ?string $tenantId = null,
        array $context = [],
    ): string {
        $tenantId = $tenantId ?? self::getCurrentTenantId();

        // Build context for path interpolation
        $pathContext = array_merge([
            'tenant' => $tenantId,
            // 'resource_type' => $model ? self::getResourceType($model) : 'resource',
            'resource_type' => $fileType->value ?? 'resource',
            'record_id' => $model?->id ?? 'new',
        ], $context);

        return self::interpolatePath($fileType->getBasePath(), $pathContext);
    }

    /**
     * Get the disk name for a file type
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @return string The disk name
     */
    public static function getDisk(FileTypeEnum $fileType): string
    {
        return self::getCustomConfiguration($fileType, 'disk') ?? $fileType->getDisk();
    }

    /**
     * Get the visibility for a file type
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @return string The visibility level (public or private)
     */
    public static function getVisibility(FileTypeEnum $fileType): string
    {
        return self::getCustomConfiguration($fileType, 'visibility') ?? $fileType->getVisibility();
    }

    /**
     * Get accepted file types for a file type category
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @return array List of accepted MIME types
     */
    public static function getAcceptedTypes(FileTypeEnum $fileType): array
    {
        return self::getCustomConfiguration($fileType, 'accepted_types') ?? $fileType->getAcceptedTypes();
    }

    /**
     * Get maximum file size in kilobytes
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @return int Maximum size in KB
     */
    public static function getMaxSizeKb(FileTypeEnum $fileType): int
    {
        return self::getCustomConfiguration($fileType, 'max_size_kb') ?? $fileType->getMaxSizeKb();
    }

    /**
     * Get the base path template for a file type
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @return string The base path template
     */
    public static function getBasePath(FileTypeEnum $fileType): string
    {
        return self::getCustomConfiguration($fileType, 'base_path') ?? $fileType->getBasePath();
    }

    /**
     * Register or override configuration for a file type
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @param  array  $config  Partial or full configuration override
     */
    public static function setCustomConfiguration(FileTypeEnum $fileType, array $config): void
    {
        $key = $fileType->value;

        // If configuration already exists, merge with existing
        if (isset(self::$customConfigurations[$key])) {
            self::$customConfigurations[$key] = array_merge(
                self::$customConfigurations[$key],
                $config,
            );
        } else {
            self::$customConfigurations[$key] = $config;
        }
    }

    /**
     * Get all supported file types
     *
     * @return array Array of FileTypeEnum cases
     */
    public static function getSupportedFileTypes(): array
    {
        return FileTypeEnum::cases();
    }

    /**
     * Get custom configuration value for a file type
     *
     * @param  FileTypeEnum  $fileType  File type enum
     * @param  string  $key  Configuration key
     * @return mixed|null The configuration value or null if not set
     */
    private static function getCustomConfiguration(FileTypeEnum $fileType, string $key): mixed
    {
        return self::$customConfigurations[$fileType->value][$key] ?? null;
    }

    /**
     * Get the current tenant ID
     *
     * @return string The tenant ID
     */
    private static function getCurrentTenantId(): string
    {
        try {
            $tenant = filament()->getTenant();

            return (string) ($tenant?->ruc ?? $tenant?->id ?? 'default');
        } catch (Throwable) {
            return 'default';
        }
    }

    /**
     * Get the resource type from a model
     *
     * @return string The resource type (lowercase plural table name or custom identifier)
     */
    private static function getResourceType(Model $model): string
    {
        // Try to get custom resource type from model
        if (method_exists($model, 'getStorageResourceType')) {
            return $model->getStorageResourceType();
        }

        // Default to table name
        return $model->getTable();
    }

    /**
     * Interpolate variables in a path template
     *
     * @param  string  $path  Path template with {variable} placeholders
     * @param  array<string, mixed>  $context  Variables to interpolate
     * @return string The interpolated path
     */
    private static function interpolatePath(string $path, array $context): string
    {
        return preg_replace_callback(
            '/\{([^}]+)\}/',
            fn ($matches) => (string) ($context[$matches[1]] ?? $matches[0]),
            $path,
        );
    }
}
