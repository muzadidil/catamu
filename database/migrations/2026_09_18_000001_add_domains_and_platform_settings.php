<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->unique()->after('id');
            $table->boolean('lifetime')->default(false)->after('plan');
        });

        Schema::create('tenant_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->timestamps();
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->string('source', 10)->default('staff')->after('created_by');
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        foreach (DB::table('tenants')->whereNull('slug')->get() as $tenant) {
            $settings = json_decode($tenant->settings ?? '[]', true) ?: [];
            $base = Str::slug($settings['office'] ?? 'Kantor Utama') ?: 'kantor';
            $slug = $base;
            for ($i = 2; DB::table('tenants')->where('slug', $slug)->exists(); $i++) {
                $slug = "{$base}-{$i}";
            }
            DB::table('tenants')->where('id', $tenant->id)->update(['slug' => $slug]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
        Schema::table('guests', fn (Blueprint $table) => $table->dropColumn('source'));
        Schema::dropIfExists('tenant_slug_redirects');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'lifetime']);
        });
    }
};
