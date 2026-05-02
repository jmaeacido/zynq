<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('discount_type', ['regular', 'promo', 'manual', 'senior', 'pwd', 'solo_parent']);
            $table->enum('value_type', ['amount', 'percent'])->default('amount');
            $table->decimal('value', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->string('reason')->nullable();
            $table->string('reference_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'sale_id', 'discount_type']);
        });

        Schema::create('sale_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->decimal('vat_rate', 8, 4)->default(12);
            $table->decimal('gross_sales', 14, 2)->default(0);
            $table->decimal('vatable_sales', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('vat_exempt_sales', 14, 2)->default(0);
            $table->decimal('zero_rated_sales', 14, 2)->default(0);
            $table->decimal('non_vat_sales', 14, 2)->default(0);
            $table->decimal('discounts', 14, 2)->default(0);
            $table->decimal('net_sales', 14, 2)->default(0);
            $table->decimal('total_amount_due', 14, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('sale_id');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_taxes');
        Schema::dropIfExists('sale_discounts');
    }
};
