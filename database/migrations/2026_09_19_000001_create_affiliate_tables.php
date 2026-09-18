<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('referral_code', 24)->nullable()->unique()->after('slug');
            $table->foreignId('referred_by_tenant_id')->nullable()->after('referral_code')->constrained('tenants')->nullOnDelete();
            $table->unsignedInteger('referral_visits')->default(0)->after('referred_by_tenant_id');
            $table->string('payout_bank', 40)->nullable()->after('referral_visits');
            $table->string('payout_account', 40)->nullable()->after('payout_bank');
            $table->string('payout_name', 120)->nullable()->after('payout_account');
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            // Rekening disalin saat pengajuan agar riwayat tetap benar walau owner menggantinya.
            $table->string('bank', 40);
            $table->string('account', 40);
            $table->string('account_name', 120);
            $table->string('status', 20)->default('pending')->index();
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_tenant_id')->constrained('tenants')->cascadeOnDelete();
            // Kantor yang diajak boleh hilang; komisi yang sudah didapat tidak ikut terhapus.
            $table->foreignId('referred_tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('referred_office', 150);
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->unsignedInteger('base_amount');
            $table->unsignedTinyInteger('rate');
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->index(['referrer_tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('payout_requests');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['referred_by_tenant_id']);
            $table->dropUnique(['tenants_referral_code_unique']);
            $table->dropColumn(['referral_code', 'referred_by_tenant_id', 'referral_visits', 'payout_bank', 'payout_account', 'payout_name']);
        });
    }
};
