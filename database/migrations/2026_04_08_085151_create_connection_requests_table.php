<?php

use App\Enums\ConnectionRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('connection_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();

            $table->enum('status', array_column(ConnectionRequestStatus::cases(), 'value'))
                ->default(ConnectionRequestStatus::Pending->value);

            $table->text('message')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            // Generated columns allow DB-level uniqueness for active requests in either direction.
            $table->unsignedBigInteger('user_pair_min_id')->storedAs(
                'CASE WHEN sender_id < receiver_id THEN sender_id ELSE receiver_id END'
            );
            $table->unsignedBigInteger('user_pair_max_id')->storedAs(
                'CASE WHEN sender_id > receiver_id THEN sender_id ELSE receiver_id END'
            );
            $table->string('is_active')->storedAs(
                "CASE WHEN status IN ('pending','accepted','payment_pending','partially_paid','connected') THEN 1 ELSE 0 END"
            );
            $table->string('active_status_key')->nullable()->storedAs(
                "CASE WHEN status IN ('pending','accepted','payment_pending','partially_paid','connected') THEN 'active' ELSE NULL END"
            );

            $table->index(['sender_id', 'status']);
            $table->index(['receiver_id', 'status']);
            $table->index(['sender_id', 'receiver_id']);
            $table->index('status');

            $table->unique(
                ['user_pair_min_id', 'user_pair_max_id', 'active_status_key'],
                'connection_requests_unique_active_pair'
            );
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE connection_requests
                 ADD CONSTRAINT chk_connection_requests_sender_receiver_diff
                 CHECK (sender_id <> receiver_id)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connection_requests');
    }
};
