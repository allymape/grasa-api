<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->unsigned();
            $table->enum('method', array_column(PaymentMethod::cases(), 'value'));
            $table->string('reference');
            $table->enum('status', array_column(PaymentStatus::cases(), 'value'))
                ->default(PaymentStatus::Pending->value);
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->unique(['connection_request_id', 'payer_id'], 'payments_connection_request_id_payer_id_unique');
            $table->unique('reference');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE payments
                 ADD CONSTRAINT chk_payments_amount_positive
                 CHECK (amount > 0)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
