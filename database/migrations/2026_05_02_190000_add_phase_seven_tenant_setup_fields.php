<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('license_key')->nullable()->unique()->after('contact_phone');
            $table->timestamp('onboarding_completed_at')->nullable()->after('invoice_footer');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropUnique(['license_key']);
            $table->dropColumn(['license_key', 'onboarding_completed_at']);
        });
    }
};
