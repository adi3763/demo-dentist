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
        $statuses = [
            'pending',
            'confirmed',
            'rejected',
            'rescheduled',
            'completed',
            'cancelled',
        ];

        $this->updateStatusColumn($statuses);

        Schema::table('appointments', function (Blueprint $table) {
            // Reason when doctor rejects
            if (! Schema::hasColumn('appointments', 'rejected_reason')) {
                $table->text('rejected_reason')->nullable()->after('reschedule_reason');
            }

            if (! Schema::hasColumn('appointments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('rejected_reason');
            }

            if (! Schema::hasColumn('appointments', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $columns = array_filter(
                ['rejected_reason', 'approved_at', 'rejected_at'],
                fn (string $column): bool => Schema::hasColumn('appointments', $column)
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        $this->updateStatusColumn([
            'pending',
            'confirmed',
            'completed',
            'cancelled',
            'rescheduled',
        ]);
    }

    private function updateStatusColumn(array $statuses): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $allowedStatuses = collect($statuses)
                ->map(fn (string $status): string => DB::getPdo()->quote($status))
                ->implode(', ');

            DB::statement('ALTER TABLE appointments DROP CONSTRAINT IF EXISTS appointments_status_check');
            DB::statement("ALTER TABLE appointments ALTER COLUMN status TYPE VARCHAR(255), ALTER COLUMN status SET DEFAULT 'pending', ALTER COLUMN status SET NOT NULL");
            DB::statement("ALTER TABLE appointments ADD CONSTRAINT appointments_status_check CHECK (status IN ({$allowedStatuses}))");

            return;
        }

        Schema::table('appointments', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('pending')->change();
        });
    }
};
