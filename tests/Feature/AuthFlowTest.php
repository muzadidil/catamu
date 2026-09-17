<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'catamu.super_admin_emails' => ['admin@catamu.test'],
        ]);
    }

    private function fakeGoogle(string $id, string $email, bool $verified = true): void
    {
        $googleUser = (new GoogleUser)
            ->setRaw(['email_verified' => $verified])
            ->map(['id' => $id, 'name' => 'Pengguna Google', 'email' => $email]);

        Socialite::shouldReceive('driver->redirectUrl->user')->andReturn($googleUser);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $owner = $this->createOwner();

        $this->get($owner->tenant->appUrl())->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('Masuk dengan Google')->assertSee('lock-card', false);
    }

    public function test_first_google_login_creates_office_with_trial_and_default_departments(): void
    {
        $this->fakeGoogle('g-1', 'Owner@Example.com');

        $response = $this->get(route('google.callback'));

        $owner = User::where('email', 'owner@example.com')->firstOrFail();
        $response->assertRedirect($owner->tenant->appUrl());
        $this->assertSame('kantor-utama', $owner->tenant->slug);
        $this->assertAuthenticatedAs($owner);
        $this->assertTrue($owner->isOwner());
        $this->assertSame('trial', $owner->tenant->subscriptionState());
        $this->assertCount(8, $owner->tenant->departments);
        $this->assertSame($owner->tenant_id, session('lock_tenant_id'));

        $this->get($owner->tenant->appUrl())->assertOk()->assertSee('window.CATAMU_STATE', false)->assertSee('id="page-dashboard"', false);
    }

    public function test_repeated_google_login_reuses_existing_office(): void
    {
        $this->fakeGoogle('g-1', 'owner@example.com');
        $this->get(route('google.callback'));
        auth()->logout();
        $this->get(route('google.callback'));

        $this->assertSame(1, Tenant::count());
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->fakeGoogle('g-2', 'x@example.com', false);

        $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_super_admin_google_login_goes_to_admin_panel(): void
    {
        $this->fakeGoogle('g-admin', 'admin@catamu.test');

        $this->get(route('google.callback'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('login'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Ringkasan');
        $this->assertSame(0, Tenant::count());
    }

    public function test_team_member_logs_in_with_email_or_phone(): void
    {
        $owner = $this->createOwner();
        $member = $this->createTeamMember($owner->tenant, attributes: ['email' => 'resepsionis@kantor.id', 'phone' => '0812-3456-7890', 'phone_key' => '081234567890']);

        $this->post(route('login.team'), ['credential' => 'RESEPSIONIS@kantor.id', 'password' => 'salah'])
            ->assertRedirect(route('login'))->assertSessionHas('error', 'Akun atau password anggota tidak sesuai.');
        $this->assertGuest();

        $this->post(route('login.team'), ['credential' => '081234567890', 'password' => 'rahasia123'])
            ->assertRedirect($owner->tenant->appUrl());
        $this->assertAuthenticatedAs($member);
    }

    public function test_inactive_team_member_cannot_log_in_and_is_kicked_out(): void
    {
        $owner = $this->createOwner();
        $member = $this->createTeamMember($owner->tenant, attributes: ['email' => 'staf@kantor.id']);

        $this->actingAs($member)->getJson($this->api('/state'))->assertOk();
        $member->update(['active' => false]);
        $this->getJson($this->api('/state'))->assertUnauthorized();

        $this->post(route('login.team'), ['credential' => 'staf@kantor.id', 'password' => 'rahasia123']);
        $this->assertGuest();
    }

    public function test_lock_keeps_device_bound_to_office_and_pin_unlocks_owner(): void
    {
        $owner = $this->createOwner();
        $owner->update(['pin' => '123456']);

        $this->actingAs($owner)->postJson(route('lock', ['slug' => $owner->tenant->slug]))->assertOk()->assertJson(['redirect' => route('login')]);
        $this->assertGuest();
        $this->assertSame($owner->tenant_id, session('lock_tenant_id'));

        $this->get(route('login'))->assertSee('Sesi aplikasi dikunci')->assertSee('id="unlockPin"', false);

        $this->post(route('login.pin'), ['pin' => '000000'])->assertSessionHas('error', 'PIN salah.');
        $this->assertGuest();

        $this->post(route('login.pin'), ['pin' => '123456'])->assertRedirect($owner->tenant->appUrl());
        $this->assertAuthenticatedAs($owner);
    }

    public function test_pin_login_requires_a_locked_device(): void
    {
        $owner = $this->createOwner();
        $owner->update(['pin' => '1234']);

        $this->post(route('login.pin'), ['pin' => '1234'])->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_pin_brute_force_is_rate_limited(): void
    {
        $owner = $this->createOwner();
        $owner->update(['pin' => '1234']);
        $this->withSession(['lock_tenant_id' => $owner->tenant_id]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.pin'), ['pin' => '9999']);
        }

        $this->post(route('login.pin'), ['pin' => '1234'])->assertSessionHas('error', fn ($message) => str_contains($message, 'Terlalu banyak percobaan PIN'));
        $this->assertGuest();
    }
}
