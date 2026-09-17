<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['catamu.super_admin_emails' => ['admin@catamu.test']]);
    }

    private function superAdmin(): User
    {
        return User::create(['type' => User::TYPE_SUPER_ADMIN, 'name' => 'Admin Pusat', 'email' => 'admin@catamu.test']);
    }

    private function renameOffice(User $owner, string $office)
    {
        return $this->actingAs($owner)->putJson($this->api('/settings/application', $owner), [
            'office' => $office, 'theme' => 'system', 'dateFormat' => 'long', 'timeFormat' => '24', 'defaultPeople' => 1, 'guestVoiceEnabled' => true,
        ]);
    }

    public function test_landing_page_shows_trial_and_price(): void
    {
        PlatformSetting::put('trial_days', 14);

        $this->get(route('landing'))->assertOk()
            ->assertSee('Catat setiap tamu kantor')
            ->assertSee('Gratis 14 hari')
            ->assertSee('Rp99.000')
            ->assertSee(route('login'), false);
    }

    public function test_unknown_host_redirects_to_main_domain(): void
    {
        $this->get('http://127.0.0.1:8000/apa-saja')->assertRedirect('http://catamu.test:8000/');
        $this->get('http://cekin.catamu.test/')->assertRedirect(route('landing'));
    }

    public function test_office_slug_follows_name_and_old_links_redirect(): void
    {
        $owner = $this->createOwner();
        $oldSlug = $owner->tenant->slug;

        $this->renameOffice($owner, 'PT Maju Bersama')->assertOk()
            ->assertJsonPath('links.appUrl', 'http://cekin.catamu.test/pt-maju-bersama/app')
            ->assertJsonPath('links.checkinUrl', 'http://cekin.catamu.test/pt-maju-bersama');

        $tenant = $owner->tenant->fresh();
        $this->assertSame('pt-maju-bersama', $tenant->slug);

        $this->get("http://cekin.catamu.test/{$oldSlug}")->assertRedirect($tenant->checkinUrl())->assertStatus(301);
        $this->actingAs($owner->fresh())->get("http://cekin.catamu.test/{$oldSlug}/app/qr-cekin")->assertRedirect($tenant->appUrl().'/qr-cekin');
        $this->getJson("http://cekin.catamu.test/{$oldSlug}/app/api/state")->assertOk();

        $other = $this->createOwner('lain@example.com');
        $this->renameOffice($other, 'PT Maju Bersama')->assertOk()->assertJsonPath('links.checkinUrl', 'http://cekin.catamu.test/pt-maju-bersama-2');
        $this->renameOffice($other->fresh(), 'Kantor Utama')->assertOk();
        $this->assertNotSame($oldSlug, $other->tenant->fresh()->slug, 'Slug lama milik kantor lain tidak boleh direbut.');
    }

    public function test_user_cannot_open_another_offices_app(): void
    {
        $ownerA = $this->createOwner('a@example.com');
        $ownerB = $this->createOwner('b@example.com');

        $this->actingAs($ownerA)->get($ownerB->tenant->appUrl())->assertRedirect($ownerA->tenant->appUrl());
        $this->getJson($this->api('/state', $ownerB))->assertNotFound();
    }

    public function test_public_self_checkin_creates_guest_and_notifies_office(): void
    {
        $owner = $this->createOwner();
        $tenant = $owner->tenant;
        $department = $tenant->departments()->where('code', 'HRD')->first();

        $this->get($tenant->checkinUrl())->assertOk()
            ->assertSee('Cek-in Tamu')
            ->assertSee('Kantor Utama')
            ->assertSee('HRD (HRD)');

        $this->postJson(route('cekin.store', ['slug' => $tenant->slug]), $this->guestPayload([
            'departmentId' => (string) $department->id,
            'photo' => $this->pngDataUrl(),
            'signature' => $this->pngDataUrl(),
        ]))->assertCreated()->assertJson(['name' => 'Budi Santoso']);

        $guest = Guest::firstOrFail();
        $this->assertSame('self', $guest->source);
        $this->assertNull($guest->created_by);
        $this->assertSame('HRD', $guest->meet);
        $this->assertSame('Tamu cek-in mandiri', $tenant->appNotifications()->first()->title);
    }

    public function test_self_checkin_validates_and_blocks_spam_and_expired_offices(): void
    {
        $owner = $this->createOwner();
        $tenant = $owner->tenant;
        $url = route('cekin.store', ['slug' => $tenant->slug]);

        $this->postJson($url, $this->guestPayload(['name' => '', 'purpose' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'purpose']);
        $this->postJson($url, $this->guestPayload(['website' => 'http://spam.test']))->assertStatus(422);

        $tenant->update(['trial_expires_at' => now()->subMinute()]);
        $this->get($tenant->checkinUrl())->assertOk()->assertSee('Cek-in mandiri sedang tidak tersedia');
        $this->postJson($url, $this->guestPayload())->assertForbidden();

        $this->assertSame(0, Guest::count());
        $this->get('http://cekin.catamu.test/kantor-tidak-ada')->assertNotFound();
    }

    public function test_app_page_exposes_checkin_links_manifest_and_qr_poster(): void
    {
        $owner = $this->createOwner();
        $tenant = $owner->tenant;

        $this->actingAs($owner)->get($tenant->appUrl())->assertOk()
            ->assertSee($tenant->checkinUrl(), false)
            ->assertSee('Cetak QR Cek-in')
            ->assertSee('content="'.$tenant->appUrl().'"', false);

        auth()->logout();
        $this->get($tenant->appUrl().'/manifest.webmanifest')->assertOk()
            ->assertJsonPath('scope', $tenant->appUrl().'/')
            ->assertJsonPath('start_url', $tenant->appUrl().'/');

        $this->actingAs($owner)->get($tenant->appUrl().'/service-worker.js')->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
        $this->get($tenant->appUrl().'/qr-cekin')->assertOk()->assertSee('<svg', false)->assertSee('Scan untuk Cek-in');
    }

    public function test_super_admin_sets_trial_days_for_new_offices(): void
    {
        $existing = $this->createOwner('lama@example.com');

        $this->actingAs($this->superAdmin())->post(route('admin.settings.trial'), ['trial_days' => 0])->assertSessionHasErrors('trial_days');
        $this->post(route('admin.settings.trial'), ['trial_days' => 14])->assertRedirect()->assertSessionHas('toast');
        $this->assertSame(14, PlatformSetting::trialDays());

        $new = $this->createOwner('baru@example.com');
        $this->assertSame('Trial 14 Hari', $new->tenant->plan);
        $this->assertEqualsWithDelta(now()->addDays(14)->timestamp, $new->tenant->trial_expires_at->timestamp, 5);
        $this->assertSame('Trial 3 Hari', $existing->tenant->fresh()->plan);

        $this->actingAs($new->fresh())->getJson($this->api('/state'))->assertJsonPath('subscription.trialDays', 14);
    }

    public function test_super_admin_activates_yearly_and_lifetime_plans(): void
    {
        $owner = $this->createOwner();
        $tenant = $owner->tenant;
        $tenant->update(['trial_expires_at' => now()->subDay()]);
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.tenants.yearly', $tenant))->assertRedirect();
        $tenant->refresh();
        $this->assertSame('active', $tenant->subscriptionState());
        $this->assertEqualsWithDelta(now()->addDays(365)->timestamp, $tenant->expires_at->timestamp, 5);

        $tenant->update(['expires_at' => now()->subDay()]);
        $this->post(route('admin.tenants.lifetime', $tenant))->assertRedirect();
        $tenant->refresh();
        $this->assertTrue($tenant->lifetime);
        $this->assertSame('active', $tenant->subscriptionState());
        $this->assertSame('Lifetime', $tenant->subscriptionLabel());

        $this->actingAs($owner->fresh())->getJson($this->api('/state'))->assertJsonPath('subscription.lifetime', true);
        $this->postJson($this->api('/guests'), $this->guestPayload())->assertCreated();

        $this->actingAs($admin)->post(route('admin.tenants.lifetime', $tenant))->assertRedirect();
        $this->assertSame('expired', $tenant->fresh()->subscriptionState());
        $this->assertSame(['Langganan diaktifkan admin', 'Paket Lifetime aktif', 'Paket Lifetime dinonaktifkan'],
            $tenant->appNotifications()->orderBy('id')->pluck('title')->filter(fn ($title) => ! str_contains($title, 'Tamu'))->values()->all());
    }

    public function test_super_admin_deletes_office_only_with_confirmation(): void
    {
        $owner = $this->createOwner();
        $tenant = $owner->tenant;
        $this->actingAs($owner)->postJson($this->api('/guests'), $this->guestPayload(['photo' => $this->pngDataUrl()]))->assertCreated();

        $this->actingAs($this->superAdmin())->delete(route('admin.tenants.destroy', $tenant), ['confirmation' => 'hapus'])->assertRedirect();
        $this->assertNotNull(Tenant::find($tenant->id));

        $this->delete(route('admin.tenants.destroy', $tenant), ['confirmation' => 'HAPUS'])->assertRedirect(route('admin.tenants'));
        $this->assertNull(Tenant::find($tenant->id));
        $this->assertSame([], Storage::disk('local')->allFiles('tenants'));
    }

    public function test_super_admin_searches_and_filters_offices(): void
    {
        $trial = $this->createOwner('rina@kantor-a.test');
        $this->actingAs($trial);
        $this->renameOffice($trial, 'PT Alpha Sentosa')->assertOk();

        $active = $this->createOwner('budi@kantor-b.test');
        $this->renameOffice($active, 'CV Beta Jaya')->assertOk();
        $active->tenant->update(['activated_at' => now(), 'expires_at' => now()->addYear()]);

        $lifetime = $this->createOwner('sari@kantor-c.test');
        $lifetime->tenant->update(['lifetime' => true, 'plan' => 'Lifetime', 'activated_at' => now()]);

        $this->actingAs($this->superAdmin());

        $this->get(route('admin.tenants'))->assertOk()
            ->assertSee('Semua <span>(3)</span>', false)
            ->assertSee('Trial <span>(1)</span>', false)
            ->assertSee('Aktif <span>(1)</span>', false)
            ->assertSee('Lifetime <span>(1)</span>', false)
            ->assertSee('Aksi Super Admin')
            ->assertSee('Jadikan Lifetime');

        $this->get(route('admin.tenants', ['status' => 'aktif']))->assertSee('CV Beta Jaya')->assertDontSee('PT Alpha Sentosa');
        $this->get(route('admin.tenants', ['q' => 'rina@kantor-a']))->assertSee('PT Alpha Sentosa')->assertDontSee('CV Beta Jaya');
        $this->get(route('admin.tenants', ['q' => 'beta']))->assertSee('CV Beta Jaya')->assertDontSee('PT Alpha Sentosa');
        $this->get(route('admin.tenants.show', $active->tenant))->assertOk()->assertSee('Riwayat pembayaran')->assertSee('budi@kantor-b.test');
    }

    public function test_non_admin_cannot_reach_backoffice(): void
    {
        $owner = $this->createOwner();

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->actingAs($owner)->get(route('admin.dashboard'))->assertForbidden();
        $this->post(route('admin.settings.trial'), ['trial_days' => 30])->assertForbidden();
        $this->assertSame(3, PlatformSetting::trialDays());
    }
}
