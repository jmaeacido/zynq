<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sale_sync_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('terminal_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('offline_reference');
            $table->string('payload_hash', 128);
            $table->enum('status', ['pending', 'synced', 'conflict', 'failed'])->default('pending');
            $table->timestamp('created_offline_at');
            $table->timestamp('synced_at')->nullable();
            $table->json('payload');
            $table->json('conflicts')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'idempotency_key']);
            $table->unique(['tenant_id', 'branch_id', 'terminal_id', 'offline_reference'], 'offline_sale_reference_unique');
            $table->index(['tenant_id', 'branch_id', 'terminal_id', 'status'], 'offline_sale_sync_scope_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sale_sync_records');
    }
};
