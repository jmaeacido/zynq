<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_invoice_ranges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('terminal_id')->constrained()->restrictOnDelete();
            $table->string('document_type', 32)->default('sales_invoice');
            $table->unsignedBigInteger('range_start');
            $table->unsignedBigInteger('range_end');
            $table->unsignedBigInteger('next_number');
            $table->string('status', 32)->default('active');
            $table->foreignId('reserved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'terminal_id', 'document_type', 'status'], 'offline_invoice_range_scope_status_index');
            $table->index(['tenant_id', 'branch_id', 'terminal_id', 'document_type', 'range_start', 'range_end'], 'offline_invoice_range_bounds_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_invoice_ranges');
    }
};
