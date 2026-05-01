<?php

declare(strict_types=1);

namespace Modules\Core\Providers\Concerns;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait HandlesModuleConfiguration
{
    protected function registerModuleConfig(string $moduleBasePath, string $moduleNameLower, array $keyAliases = []): void
    {
        $configPath = $this->moduleConfigPath($moduleBasePath);

        if (! is_dir($configPath)) {
            return;
        }

        foreach ($this->moduleConfigFiles($configPath) as $relativePath => $filePath) {
            $this->mergeConfigRecursively(
                $filePath,
                $this->configKeyFor($relativePath, $moduleNameLower, $keyAliases)
            );
        }
    }

    protected function publishModuleConfig(string $moduleBasePath): void
    {
        $configPath = $this->moduleConfigPath($moduleBasePath);

        if (! is_dir($configPath)) {
            return;
        }

        foreach ($this->moduleConfigFiles($configPath) as $relativePath => $filePath) {
            $this->publishes([$filePath => config_path($relativePath)], 'config');
        }
    }

    private function moduleConfigPath(string $moduleBasePath): string
    {
        return $moduleBasePath.'/config';
    }

    /**
     * @return array<string, string>
     */
    private function moduleConfigFiles(string $configPath): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $files[$relativePath] = $file->getPathname();
        }

        return $files;
    }

    private function configKeyFor(string $relativePath, string $moduleNameLower, array $keyAliases): string
    {
        if (isset($keyAliases[$relativePath])) {
            return $keyAliases[$relativePath];
        }

        if ($relativePath === 'config.php') {
            return $moduleNameLower;
        }

        $configKey = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
        $segments = explode('.', $moduleNameLower.'.'.$configKey);
        $normalized = [];

        foreach ($segments as $segment) {
            if (end($normalized) !== $segment) {
                $normalized[] = $segment;
            }
        }

        return implode('.', $normalized);
    }

    private function mergeConfigRecursively(string $path, string $key): void
    {
        $existing = config($key, []);
        $moduleConfig = require $path;

        config([$key => array_replace_recursive($existing, $moduleConfig)]);
    }
}
