<?php

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
        if (! $this->indexExists('payments', 'payments_connection_request_id_payer_id_unique')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->unique(
                    ['connection_request_id', 'payer_id'],
                    'payments_connection_request_id_payer_id_unique'
                );
            });
        }

        if ($this->indexExists('payments', 'payments_connection_request_id_unique')) {
            if ($this->isMySql() && $this->foreignKeyExists('payments', 'payments_connection_request_id_foreign')) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->dropForeign('payments_connection_request_id_foreign');
                });
            }

            Schema::table('payments', function (Blueprint $table): void {
                $table->dropUnique('payments_connection_request_id_unique');
            });

            if ($this->isMySql() && ! $this->foreignKeyExists('payments', 'payments_connection_request_id_foreign')) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->foreign('connection_request_id')
                        ->references('id')
                        ->on('connection_requests')
                        ->cascadeOnDelete();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->indexExists('payments', 'payments_connection_request_id_unique')) {
            Schema::table('payments', function (Blueprint $table): void {
                $table->unique('connection_request_id', 'payments_connection_request_id_unique');
            });
        }

        if ($this->indexExists('payments', 'payments_connection_request_id_payer_id_unique')) {
            if ($this->isMySql() && $this->foreignKeyExists('payments', 'payments_connection_request_id_foreign')) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->dropForeign('payments_connection_request_id_foreign');
                });
            }

            Schema::table('payments', function (Blueprint $table): void {
                $table->dropUnique('payments_connection_request_id_payer_id_unique');
            });

            if ($this->isMySql() && ! $this->foreignKeyExists('payments', 'payments_connection_request_id_foreign')) {
                Schema::table('payments', function (Blueprint $table): void {
                    $table->foreign('connection_request_id')
                        ->references('id')
                        ->on('connection_requests')
                        ->cascadeOnDelete();
                });
            }
        }
    }

    private function isMySql(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");

            foreach ($rows as $row) {
                $name = is_array($row) ? ($row['name'] ?? null) : ($row->name ?? null);
                if ($name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $rows = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$database, $table, $indexName]
            );

            return ! empty($rows);
        }

        if ($driver === 'pgsql') {
            $rows = DB::select(
                'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                [$table, $indexName]
            );

            return ! empty($rows);
        }

        return false;
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $database = $connection->getDatabaseName();
            $rows = DB::select(
                'SELECT 1 FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_type = ? AND constraint_name = ? LIMIT 1',
                [$database, $table, 'FOREIGN KEY', $constraintName]
            );

            return ! empty($rows);
        }

        if ($driver === 'pgsql') {
            $rows = DB::select(
                'SELECT 1 FROM information_schema.table_constraints WHERE table_schema = current_schema() AND table_name = ? AND constraint_type = ? AND constraint_name = ? LIMIT 1',
                [$table, 'FOREIGN KEY', $constraintName]
            );

            return ! empty($rows);
        }

        return false;
    }
};
