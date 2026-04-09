<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('profiles', 'date_of_birth')) {
            Schema::table('profiles', function (Blueprint $table): void {
                $table->date('date_of_birth')->nullable()->after('age')->index('profiles_date_of_birth_idx');
            });
        }

        DB::table('profiles')
            ->select(['id', 'age', 'date_of_birth'])
            ->whereNull('date_of_birth')
            ->orderBy('id')
            ->chunkById(200, function ($profiles): void {
                foreach ($profiles as $profile) {
                    $age = max(18, (int) ($profile->age ?? 18));

                    DB::table('profiles')
                        ->where('id', (int) $profile->id)
                        ->update([
                            'date_of_birth' => Carbon::today()->subYears($age)->toDateString(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('profiles', 'date_of_birth')) {
            Schema::table('profiles', function (Blueprint $table): void {
                $table->dropColumn('date_of_birth');
            });
        }
    }
};
