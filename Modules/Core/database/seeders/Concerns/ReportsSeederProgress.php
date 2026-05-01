<?php

declare(strict_types=1);

namespace Modules\Core\Database\Seeders\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait ReportsSeederProgress
{
    protected function reportCreated(int $created): void
    {
        if ($created === 0) {
            $this->reportSkipped();

            return;
        }

        $this->writeInfo($this->seederName().': '.$this->formatSegment($created, 'record created', 'records created').'.');
    }

    protected function reportCreatedAndUpdated(int $created, int $updated): void
    {
        $segments = [];

        if ($created > 0) {
            $segments[] = $this->formatSegment($created, 'record created', 'records created');
        }

        if ($updated > 0) {
            $segments[] = $this->formatSegment($updated, 'record updated', 'records updated');
        }

        if ($segments === []) {
            $this->reportSkipped();

            return;
        }

        $this->writeInfo($this->seederName().': '.implode(', ', $segments).'.');
    }

    protected function reportSynchronized(int $synchronized, string $noun = 'record'): void
    {
        if ($synchronized === 0) {
            $this->reportSkipped("0 {$noun}s synchronized");

            return;
        }

        $singular = "{$noun} synchronized";
        $plural = "{$noun}s synchronized";

        $this->writeInfo($this->seederName().': '.$this->formatSegment($synchronized, $singular, $plural).'.');
    }

    protected function reportSkipped(string $detail = '0 records created'): void
    {
        $this->writeInfo($this->seederName().": skipped, {$detail}.");
    }

    protected function tallyModelChange(Model $model, int &$created, int &$updated): void
    {
        if ($model->wasRecentlyCreated) {
            $created++;

            return;
        }

        $updated++;
    }

    protected function countPermissionProvisioningRecords(int $companyId, callable $callback): int
    {
        $before = $this->permissionProvisioningSnapshot($companyId);

        $callback();

        $after = $this->permissionProvisioningSnapshot($companyId);

        return max(0, ($after['roles'] + $after['role_permissions']) - ($before['roles'] + $before['role_permissions']));
    }

    private function permissionProvisioningSnapshot(int $companyId): array
    {
        $rolesTable = (string) config('permission.table_names.roles', 'roles');
        $rolePermissionsTable = (string) config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $teamForeignKey = (string) config('permission.column_names.team_foreign_key', 'company_id');

        $roleIds = DB::table($rolesTable)
            ->where($teamForeignKey, $companyId)
            ->pluck('id');

        return [
            'roles' => $roleIds->count(),
            'role_permissions' => $roleIds->isEmpty()
                ? 0
                : DB::table($rolePermissionsTable)->whereIn('role_id', $roleIds)->count(),
        ];
    }

    private function formatSegment(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }

    private function seederName(): string
    {
        return class_basename(static::class);
    }

    private function writeInfo(string $message): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->info($message);
    }

    private function writeWarn(string $message): void
    {
        if ($this->command === null) {
            return;
        }

        $this->command->warn($message);
    }
}
