<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // company_id is part of the composite PK in both pivot tables.
        // PostgreSQL does not allow NULLs in primary key columns, so we must:
        //   1. Drop the existing PK
        //   2. Make company_id nullable
        //   3. Recreate uniqueness via UNIQUE NULLS NOT DISTINCT (PG 15+)
        //      so that (NULL, role_id, model_id, model_type) is still treated as unique.
        // This allows superadmin (and other global roles/permissions) to be assigned
        // without a tenant context (company_id = null).

        foreach (['model_has_roles' => 'model_has_roles_pkey', 'model_has_permissions' => 'model_has_permissions_pkey'] as $table => $pk) {
            DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$pk}");
            DB::statement("ALTER TABLE {$table} ALTER COLUMN company_id DROP NOT NULL");
        }

        DB::statement('ALTER TABLE model_has_roles ADD CONSTRAINT model_has_roles_unique UNIQUE NULLS NOT DISTINCT (company_id, role_id, model_id, model_type)');
        DB::statement('ALTER TABLE model_has_permissions ADD CONSTRAINT model_has_permissions_unique UNIQUE NULLS NOT DISTINCT (company_id, permission_id, model_id, model_type)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE model_has_roles DROP CONSTRAINT model_has_roles_unique');
        DB::statement('ALTER TABLE model_has_permissions DROP CONSTRAINT model_has_permissions_unique');

        // Rows with company_id = null cannot be restored to a NOT NULL PK — remove them first.
        DB::statement('DELETE FROM model_has_roles WHERE company_id IS NULL');
        DB::statement('DELETE FROM model_has_permissions WHERE company_id IS NULL');

        DB::statement('ALTER TABLE model_has_roles ALTER COLUMN company_id SET NOT NULL');
        DB::statement('ALTER TABLE model_has_permissions ALTER COLUMN company_id SET NOT NULL');

        DB::statement('ALTER TABLE model_has_roles ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (company_id, role_id, model_id, model_type)');
        DB::statement('ALTER TABLE model_has_permissions ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (company_id, permission_id, model_id, model_type)');
    }
};
