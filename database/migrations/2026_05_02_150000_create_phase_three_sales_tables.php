<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('terminal_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 32)->default('sales_invoice');
            $table->string('prefix', 32)->default('SI');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedSmallInteger('padding')->default(8);
            $table->string('reset_policy', 32)->default('never');
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'terminal_id', 'document_type']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('terminal_id')->constrained()->restrictOnDelete();
            $table->foreignId('cashier_id')->constrained('users')->restrictOnDelete();
            $table->string('invoice_number');
            $table->string('invoice_title', 64)->default('Sales Invoice');
            $table->enum('status', ['completed'])->default('completed');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_total', 14, 2)->default(0);
            $table->decimal('tax_total', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2);
            $table->decimal('amount_paid', 14, 2);
            $table->decimal('change_due', 14, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'terminal_id', 'invoice_number']);
            $table->index(['tenant_id', 'branch_id', 'status', 'created_at']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('sku', 64);
            $table->string('barcode', 128)->nullable();
            $table->string('name');
            $table->string('unit', 32);
            $table->string('tax_type', 32);
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_total', 14, 2);
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
        });

        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->enum('payment_method', ['cash', 'card', 'e_wallet']);
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_tendered', 14, 2)->nullable();
            $table->string('reference_number')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('invoice_sequences');
    }
};
