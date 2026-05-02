<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offline_sale_sync_records', function (Blueprint $table): void {
            $table->timestamp('reviewed_at')->nullable()->after('synced_at');
            $table->foreignId('reviewed_by_user_id')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('reviewed_by_user_id');
            $table->foreignId('cancelled_by_user_id')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->text('resolution_reason')->nullable()->after('last_error');
            $table->json('resolution_metadata')->nullable()->after('resolution_reason');
        });

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE offline_sale_sync_records MODIFY status ENUM('pending_sync','syncing','synced','conflict','cancelled','reviewed','failed') NOT NULL DEFAULT 'pending_sync'");
        }

        DB::table('offline_sale_sync_records')->where('status', 'pending')->update(['status' => 'pending_sync']);
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE offline_sale_sync_records MODIFY status ENUM('pending','synced','conflict','failed') NOT NULL DEFAULT 'pending'");
        }

        Schema::table('offline_sale_sync_records', function (Blueprint $table): void {
            $table->dropForeign(['reviewed_by_user_id']);
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropColumn([
                'reviewed_at',
                'reviewed_by_user_id',
                'cancelled_at',
                'cancelled_by_user_id',
                'resolution_reason',
                'resolution_metadata',
            ]);
        });
    }
};
