<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'active']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('sku', 64);
            $table->string('barcode', 128)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 32)->default('pcs');
            $table->decimal('selling_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->enum('tax_type', ['VATABLE', 'VAT_EXEMPT', 'ZERO_RATED', 'NON_VAT']);
            $table->boolean('active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'sku']);
            $table->unique(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'category_id', 'active']);
        });

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_on_hand', 14, 3)->default(0);
            $table->decimal('reorder_point', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'product_id']);
            $table->index(['tenant_id', 'branch_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_stock_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('movement_type', ['stock_in', 'stock_out', 'adjustment']);
            $table->decimal('quantity_delta', 14, 3);
            $table->decimal('quantity_before', 14, 3);
            $table->decimal('quantity_after', 14, 3);
            $table->string('reference_number')->nullable();
            $table->text('reason');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'product_id']);
            $table->index(['movement_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
