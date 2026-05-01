<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Replace 'english' GIN full-text indexes with 'simple' regconfig.
        //
        // 'simple' is correct for an ERP with company names, product codes, and
        // proper nouns: it performs no stemming and has no stop words, so every
        // token is indexed as-is. 'english' was wrong because:
        //   - Spanish stop words (de, la, el, los...) are NOT filtered → noise tokens
        //   - English stemming mangles Spanish words ("proveedores" ≠ "provid*")
        //   - Proper nouns and codes should never be stemmed

        // core_business_partners — legal_name + trade_name
        DB::statement('DROP INDEX IF EXISTS ftidx_bp_names');
        DB::statement("
            CREATE INDEX ftidx_bp_names ON core_business_partners
            USING gin (
                (   to_tsvector('simple', legal_name)
                 || to_tsvector('simple', trade_name)
                )
            )
        ");

        // inv_products — name
        DB::statement('DROP INDEX IF EXISTS ftidx_products_name');
        DB::statement("
            CREATE INDEX ftidx_products_name ON inv_products
            USING gin (to_tsvector('simple', name))
        ");

        // core_users — name
        DB::statement('DROP INDEX IF EXISTS ftidx_users_name');
        DB::statement("
            CREATE INDEX ftidx_users_name ON core_users
            USING gin (to_tsvector('simple', name))
        ");

        // inv_products.tax_id — FK without index
        DB::statement('CREATE INDEX inv_products_tax_id_idx ON inv_products (tax_id) WHERE tax_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ftidx_bp_names');
        DB::statement("
            CREATE INDEX ftidx_bp_names ON core_business_partners
            USING gin (
                (   to_tsvector('english', legal_name)
                 || to_tsvector('english', trade_name)
                )
            )
        ");

        DB::statement('DROP INDEX IF EXISTS ftidx_products_name');
        DB::statement("
            CREATE INDEX ftidx_products_name ON inv_products
            USING gin (to_tsvector('english', name))
        ");

        DB::statement('DROP INDEX IF EXISTS ftidx_users_name');
        DB::statement("
            CREATE INDEX ftidx_users_name ON core_users
            USING gin (to_tsvector('english', name))
        ");

        DB::statement('DROP INDEX IF EXISTS inv_products_tax_id_idx');
    }
};
