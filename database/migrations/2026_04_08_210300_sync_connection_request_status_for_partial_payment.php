<?php

use App\Enums\ConnectionRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $statusValues = "'".implode("','", array_column(ConnectionRequestStatus::cases(), 'value'))."'";
        $activeStatuses = "'".implode("','", ConnectionRequestStatus::activeValues())."'";

        DB::statement(
            "ALTER TABLE connection_requests MODIFY status ENUM({$statusValues}) NOT NULL DEFAULT 'pending'"
        );

        DB::statement(
            "ALTER TABLE connection_requests
                MODIFY is_active TINYINT(1)
                GENERATED ALWAYS AS (CASE WHEN status IN ({$activeStatuses}) THEN 1 ELSE 0 END) STORED"
        );

        DB::statement(
            "ALTER TABLE connection_requests
                MODIFY active_status_key VARCHAR(255)
                GENERATED ALWAYS AS (CASE WHEN status IN ({$activeStatuses}) THEN 'active' ELSE NULL END) STORED"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $legacyStatuses = "'pending','accepted','rejected','payment_pending','connected','cancelled'";
        $legacyActiveStatuses = "'pending','accepted','payment_pending','connected'";

        DB::statement(
            "UPDATE connection_requests SET status='payment_pending' WHERE status='partially_paid'"
        );

        DB::statement(
            "ALTER TABLE connection_requests MODIFY status ENUM({$legacyStatuses}) NOT NULL DEFAULT 'pending'"
        );

        DB::statement(
            "ALTER TABLE connection_requests
                MODIFY is_active TINYINT(1)
                GENERATED ALWAYS AS (CASE WHEN status IN ({$legacyActiveStatuses}) THEN 1 ELSE 0 END) STORED"
        );

        DB::statement(
            "ALTER TABLE connection_requests
                MODIFY active_status_key VARCHAR(255)
                GENERATED ALWAYS AS (CASE WHEN status IN ({$legacyActiveStatuses}) THEN 'active' ELSE NULL END) STORED"
        );
    }
};
