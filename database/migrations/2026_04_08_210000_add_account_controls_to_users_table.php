<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_admin');
            }

            if (! Schema::hasColumn('users', 'is_blocked')) {
                $table->boolean('is_blocked')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('users', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable()->after('is_blocked');
            }

            if (! Schema::hasColumn('users', 'blocked_reason')) {
                $table->string('blocked_reason', 255)->nullable()->after('blocked_at');
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }

            $table->index(['is_active', 'is_blocked'], 'users_active_blocked_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            try {
                $table->dropIndex('users_active_blocked_idx');
            } catch (Throwable) {
                // no-op
            }

            foreach (['blocked_reason', 'blocked_at', 'is_blocked', 'is_active'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
