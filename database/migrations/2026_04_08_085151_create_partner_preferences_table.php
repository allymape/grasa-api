<?php

use App\Enums\BodyType;
use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\SkinTone;
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
        Schema::create('partner_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('preferred_gender', array_column(Gender::cases(), 'value'));
            $table->unsignedTinyInteger('min_age')->default(18);
            $table->unsignedTinyInteger('max_age')->default(60);
            $table->enum('preferred_religion', array_column(Religion::cases(), 'value'))->nullable();
            $table->boolean('must_have_job')->default(false);
            $table->boolean('must_be_calm')->default(false);
            $table->boolean('must_love_children')->default(false);
            $table->boolean('must_be_modest')->default(false);
            $table->boolean('must_be_respectful')->default(false);
            $table->enum('preferred_skin_tone', array_column(SkinTone::cases(), 'value'))->nullable();
            $table->enum('preferred_body_type', array_column(BodyType::cases(), 'value'))->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();

            $table->index(['preferred_gender', 'min_age', 'max_age'], 'partner_pref_gender_age_idx');
            $table->index(
                ['preferred_religion', 'preferred_body_type', 'preferred_skin_tone'],
                'partner_pref_attr_idx'
            );
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE partner_preferences
                 ADD CONSTRAINT chk_partner_preferences_age_range
                 CHECK (min_age <= max_age)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_preferences');
    }
};
