<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('approved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->enum('type', ['void', 'refund', 'partial_refund']);
            $table->string('original_invoice_number');
            $table->decimal('amount', 14, 2);
            $table->boolean('return_to_stock')->default(false);
            $table->text('reason');
            $table->json('items')->nullable();
            $table->string('transaction_hash', 128);
            $table->timestamps();

            $table->index(['tenant_id', 'type', 'created_at']);
        });

        Schema::create('financial_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('terminal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sale_reversal_id')->nullable()->constrained('sale_reversals')->restrictOnDelete();
            $table->enum('entry_type', ['sale', 'void', 'refund', 'partial_refund']);
            $table->decimal('amount', 14, 2);
            $table->string('reference_number');
            $table->json('payload');
            $table->string('transaction_hash', 128);
            $table->timestamps();

            $table->index(['tenant_id', 'entry_type', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('module');
            $table->string('record_type');
            $table->string('record_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'module', 'action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('financial_ledger_entries');
        Schema::dropIfExists('sale_reversals');
    }
};
