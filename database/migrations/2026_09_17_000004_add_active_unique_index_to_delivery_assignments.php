<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $exists = DB::selectOne(
            'SELECT 1 FROM pg_indexes WHERE indexname = ?',
            ['delivery_assignments_active_unique']
        );

        if ($exists === null) {
            DB::statement(
                "CREATE UNIQUE INDEX delivery_assignments_active_unique
                 ON delivery_assignments (order_id)
                 WHERE status IN ('assigned', 'started')"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS delivery_assignments_active_unique');
    }
};
