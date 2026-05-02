<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string('trade_name')->nullable();
            $table->text('registered_address');
            $table->string('tin', 32);
            $table->enum('taxpayer_type', ['VAT', 'NON_VAT']);
            $table->string('bir_rdo_code', 32)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->enum('license_status', ['trial', 'active', 'grace_period', 'suspended', 'disabled'])->default('trial');
            $table->date('subscription_expires_at')->nullable();
            $table->unsignedSmallInteger('grace_period_days')->default(0);
            $table->string('logo_path')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique('tin');
            $table->index(['license_status', 'active']);
        });

        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('branch_name');
            $table->string('branch_code', 32);
            $table->text('address');
            $table->text('bir_registered_address')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_code']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('terminals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('terminal_name');
            $table->string('terminal_code', 32);
            $table->string('machine_identification_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('permit_to_use_number')->nullable();
            $table->string('accreditation_number')->nullable();
            $table->string('software_version')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'terminal_code']);
            $table->index(['tenant_id', 'branch_id', 'active']);
        });

        Schema::create('tenant_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->boolean('is_sensitive')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
            $table->boolean('active')->default(true)->after('password');
            $table->index(['tenant_id', 'branch_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('active');
        });

        Schema::dropIfExists('tenant_settings');
        Schema::dropIfExists('terminals');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('tenants');
    }
};
