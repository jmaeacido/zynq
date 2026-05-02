<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('terminal_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->nullable();
            $table->decimal('actual_cash', 14, 2)->nullable();
            $table->decimal('cash_difference', 14, 2)->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'terminal_id', 'user_id', 'status'], 'cash_sessions_scope_status_index');
            $table->index(['tenant_id', 'branch_id', 'terminal_id', 'opened_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('cash_session_id')->nullable()->after('cashier_id')->constrained('cash_sessions')->restrictOnDelete();
            $table->index(['tenant_id', 'cash_session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_session_id');
        });

        Schema::dropIfExists('cash_sessions');
    }
};
