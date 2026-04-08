<?php

use App\Enums\BodyType;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\ProfileApprovalStatus;
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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->char('iso2', 2)->unique();
            $table->string('phone_code', 8);
            $table->string('flag', 16);
            $table->boolean('requires_region_district')->default(false);
            $table->timestamps();

            $table->index('phone_code');
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->timestamps();

            $table->unique(['country_id', 'name']);
            $table->index(['country_id', 'code'], 'regions_country_code_idx');
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 30)->nullable();
            $table->timestamps();

            $table->unique(['region_id', 'name']);
            $table->index(['region_id', 'code'], 'districts_region_code_idx');
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('display_name');
            $table->unsignedTinyInteger('age');
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->restrictOnDelete();
            $table->string('current_residence')
                ->comment('Free-text current city/town/neighborhood; separate from structured country/region/district.');
            $table->unsignedSmallInteger('height_cm');
            $table->enum('employment_status', array_column(EmploymentStatus::cases(), 'value'));
            $table->string('job_title')->nullable();
            $table->enum('marital_status', array_column(MaritalStatus::cases(), 'value'));
            $table->boolean('has_children')->default(false);
            $table->unsignedTinyInteger('children_count')->default(0);
            $table->enum('religion', array_column(Religion::cases(), 'value'));
            $table->enum('body_type', array_column(BodyType::cases(), 'value'))->nullable();
            $table->enum('skin_tone', array_column(SkinTone::cases(), 'value'))->nullable();
            $table->text('about_me');
            $table->text('life_outlook');
            $table->enum('approval_status', array_column(ProfileApprovalStatus::cases(), 'value'))
                ->default(ProfileApprovalStatus::Pending->value);
            $table->boolean('is_profile_complete')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['approval_status', 'is_visible'], 'profiles_approval_visible_idx');
            $table->index(['country_id', 'region_id', 'district_id'], 'profiles_country_region_district_idx');
            $table->index(['age', 'country_id', 'region_id'], 'profiles_age_country_region_idx');
            $table->index(
                ['country_id', 'region_id', 'religion', 'marital_status', 'has_children'],
                'profiles_filter_match_idx'
            );
            $table->index(['employment_status', 'is_visible'], 'profiles_employment_visible_idx');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE profiles
                 ADD CONSTRAINT chk_profiles_children_consistency
                 CHECK ((has_children = 0 AND children_count = 0) OR (has_children = 1 AND children_count > 0))'
            );

            DB::statement(
                'ALTER TABLE profiles
                 ADD CONSTRAINT chk_profiles_height_cm_range
                 CHECK (height_cm BETWEEN 100 AND 250)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('countries');
    }
};
