<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['catamu.super_admin_emails' => ['admin@catamu.test']]);
    }

    public function test_owner_manages_team_members_with_hashed_passwords(): void
    {
        $owner = $this->createOwner();

        $member = $this->actingAs($owner)->postJson($this->api('/team'), [
            'name' => 'Sari', 'email' => 'Sari@Kantor.id', 'phone' => '0812 1111 2222', 'role' => 'Viewer', 'active' => true,
            'permissions' => ['guests' => true, 'reports' => true, 'settings' => true],
            'password' => 'rahasia1', 'password_confirmation' => 'rahasia1',
        ])->assertCreated()
            ->assertJsonPath('member.email', 'sari@kantor.id')
            ->assertJsonPath('member.permissions.settings', false)
            ->assertJsonPath('member.hasPassword', true)
            ->json('member');

        $stored = User::find($member['id']);
        $this->assertTrue(Hash::check('rahasia1', $stored->password));
        $this->assertSame('081211112222', $stored->phone_key);

        $this->putJson($this->api("/team/{$member['id']}"), [
            'name' => 'Sari W', 'email' => 'sari@kantor.id', 'phone' => '', 'role' => 'Resepsionis', 'active' => true,
            'permissions' => ['guests' => true], 'password' => '', 'password_confirmation' => '',
        ])->assertOk()->assertJsonPath('member.name', 'Sari W');
        $this->assertTrue(Hash::check('rahasia1', $stored->fresh()->password));
    }

    public function test_team_email_and_phone_are_unique_across_offices(): void
    {
        $ownerA = $this->createOwner('a@example.com');
        $ownerB = $this->createOwner('b@example.com');
        $this->createTeamMember($ownerA->tenant, attributes: ['email' => 'staf@kantor.id', 'phone_key' => '0811']);

        $payload = ['name' => 'X', 'role' => 'Resepsionis', 'active' => true, 'permissions' => ['guests' => true], 'password' => 'rahasia1', 'password_confirmation' => 'rahasia1'];

        $this->actingAs($ownerB)->postJson($this->api('/team'), $payload + ['email' => 'staf@kantor.id'])
            ->assertJsonValidationErrors(['email' => 'Email sudah digunakan anggota lain.']);
        $this->postJson($this->api('/team'), $payload + ['phone' => '0811'])
            ->assertJsonValidationErrors(['phone' => 'Nomor HP sudah digunakan anggota lain.']);
    }

    public function test_only_admin_role_with_settings_permission_manages_team(): void
    {
        $owner = $this->createOwner();
        $admin = $this->createTeamMember($owner->tenant, 'Admin', ['guests' => true, 'settings' => true]);
        $receptionist = $this->createTeamMember($owner->tenant, 'Resepsionis', ['guests' => true, 'settings' => true]);
        $payload = ['name' => 'Baru', 'role' => 'Resepsionis', 'active' => true, 'permissions' => ['guests' => true], 'password' => 'rahasia1', 'password_confirmation' => 'rahasia1'];

        $this->actingAs($admin)->postJson($this->api('/team'), $payload)->assertCreated();
        $this->actingAs($receptionist)->postJson($this->api('/team'), $payload)->assertForbidden();
        $this->actingAs($admin)->putJson($this->api('/account'), ['name' => 'X'])->assertForbidden();
    }

    public function test_departments_are_unique_per_office(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)->postJson($this->api('/departments'), ['name' => 'keuangan', 'code' => 'x', 'active' => true])
            ->assertJsonValidationErrors(['name' => 'Nama departemen sudah digunakan.']);
        $created = $this->postJson($this->api('/departments'), ['name' => 'Legal', 'code' => 'lgl', 'active' => true])
            ->assertCreated()->assertJsonPath('department.code', 'LGL')->json('department');
        $this->deleteJson($this->api("/departments/{$created['id']}"))->assertOk();
    }

    public function test_owner_account_pin_and_photo(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)->putJson($this->api('/account'), ['name' => 'Bu Ani', 'pin' => '12a4', 'pin_confirmation' => '12a4'])
            ->assertJsonValidationErrors(['pin' => 'PIN harus 4–6 digit angka.']);
        $this->putJson($this->api('/account'), ['name' => 'Bu Ani', 'email' => 'owner@example.com', 'pin' => '4321', 'pin_confirmation' => '4321'])
            ->assertOk()->assertJsonPath('profile.hasPin', true)->assertJsonPath('profile.name', 'Bu Ani');
        $this->assertTrue(Hash::check('4321', $owner->fresh()->pin));

        $photoUrl = $this->postJson($this->api('/account/photo'), ['photo' => $this->pngDataUrl()])->assertOk()->json('profile.photo');
        $this->get($photoUrl)->assertOk();

        $this->deleteJson($this->api('/account/pin'))->assertOk()->assertJsonPath('profile.hasPin', false);
        $this->deleteJson($this->api('/account/pin'))->assertStatus(422);
    }

    public function test_delete_account_removes_office_data_and_files(): void
    {
        $owner = $this->createOwner();
        $member = $this->createTeamMember($owner->tenant);
        $this->actingAs($owner)->postJson($this->api('/guests'), $this->guestPayload(['photo' => $this->pngDataUrl()]))->assertCreated();

        $this->deleteJson($this->api('/account'), ['confirmation' => 'hapus'])->assertStatus(422);
        $this->deleteJson($this->api('/account'), ['confirmation' => 'HAPUS AKUN'])->assertOk()->assertJson(['redirect' => route('login')]);

        $this->assertSame(0, Tenant::count());
        $this->assertNull(User::find($member->id));
        $this->assertSame([], Storage::disk('local')->allFiles('tenants'));
        $this->assertGuest();
    }

    public function test_payment_verification_flow(): void
    {
        $owner = $this->createOwner();
        $tenant = $owner->tenant;
        $tenant->update(['trial_expires_at' => now()->subDay()]);

        $this->actingAs($owner)->postJson($this->api('/payments'), ['days' => 30, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()])
            ->assertStatus(422);
        $this->postJson($this->api('/payments'), ['days' => 365, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()])
            ->assertCreated()->assertJsonPath('subscription.pendingPayment.amount', 99000);
        $this->postJson($this->api('/payments'), ['days' => 365, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()])
            ->assertStatus(422)->assertJsonPath('message', 'Masih ada pembayaran yang menunggu verifikasi.');
        $this->assertSame('pending', $tenant->fresh()->subscriptionState());

        $payment = Payment::firstOrFail();
        $this->post(route('admin.payments.approve', $payment))->assertForbidden();
        $this->get(route('media.payment', $payment))->assertOk();

        $admin = User::create(['type' => User::TYPE_SUPER_ADMIN, 'name' => 'Admin', 'email' => 'admin@catamu.test']);
        $this->actingAs($admin)->get(route('admin.payments'))->assertOk()->assertSee($tenant->officeName());
        $this->get(route('media.payment', $payment))->assertOk();
        $this->post(route('admin.payments.approve', $payment))->assertRedirect();

        $tenant->refresh();
        $this->assertSame('active', $tenant->subscriptionState());
        $this->assertSame('1 Tahun', $tenant->plan);
        $this->assertEqualsWithDelta(now()->addDays(365)->timestamp, $tenant->expires_at->timestamp, 5);
        $this->assertSame('subscription', $tenant->appNotifications()->first()->event);

        $this->actingAs($owner)->postJson($this->api('/payments'), ['days' => 365, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()])->assertCreated();
        $renewal = Payment::where('status', 'pending')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.payments.approve', $renewal));
        $this->assertEqualsWithDelta(now()->addDays(730)->timestamp, $tenant->fresh()->expires_at->timestamp, 5);
    }

    public function test_rejected_payment_is_reported_to_office(): void
    {
        $owner = $this->createOwner();
        $this->actingAs($owner)->postJson($this->api('/payments'), ['days' => 365, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()])->assertCreated();
        $admin = User::create(['type' => User::TYPE_SUPER_ADMIN, 'name' => 'Admin', 'email' => 'admin@catamu.test']);

        $this->actingAs($admin)->post(route('admin.payments.reject', Payment::firstOrFail()), ['note' => 'Nominal kurang'])->assertRedirect();

        $this->actingAs($owner->fresh())->getJson($this->api('/state'))
            ->assertJsonPath('subscription.pendingPayment', null)
            ->assertJsonPath('subscription.lastRejectedPayment.note', 'Nominal kurang');
        $this->assertSame('trial', $owner->tenant->fresh()->subscriptionState());
    }

    public function test_team_member_cannot_submit_payment_or_open_admin(): void
    {
        $owner = $this->createOwner();
        $admin = $this->createTeamMember($owner->tenant, 'Admin', ['guests' => true, 'settings' => true, 'reports' => true]);

        $this->actingAs($admin)->postJson($this->api('/payments'), ['days' => 365, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()])->assertForbidden();
        $this->get(route('admin.payments'))->assertForbidden();
    }

    public function test_admin_panel_pages_render(): void
    {
        $owner = $this->createOwner();
        $this->actingAs($owner)->postJson($this->api('/feedbacks'), ['category' => 'Saran', 'title' => 'Tambah fitur', 'message' => 'Mohon ekspor PDF'])->assertCreated();
        $this->putJson($this->api('/rating'), ['score' => 4, 'comment' => 'Bagus'])->assertOk();
        $admin = User::create(['type' => User::TYPE_SUPER_ADMIN, 'name' => 'Admin', 'email' => 'admin@catamu.test']);

        $this->actingAs($admin);
        $this->get(route('admin.tenants'))->assertOk()->assertSee('owner@example.com');
        $this->get(route('admin.feedbacks'))->assertOk()->assertSee('Mohon ekspor PDF')->assertSee('4 dari 5 bintang');
        $this->get(route('admin.settings'))->assertOk()->assertSee('Pilih gambar QRIS merchant');
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Kantor terbaru');
    }
}
