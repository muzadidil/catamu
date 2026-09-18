<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PayoutRequest;
use App\Models\PlatformSetting;
use App\Models\ReferralCommission;
use App\Models\User;
use App\Support\Affiliate;
use App\Support\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AffiliateTest extends TestCase
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
        return User::firstOrCreate(['email' => 'admin@catamu.test'], ['type' => User::TYPE_SUPER_ADMIN, 'name' => 'Admin Pusat']);
    }

    /** Kantor B mendaftar lewat link kantor A, lalu membayar dan disetujui admin. */
    private function referredOfficePays(User $inviter, string $email = 'diajak@example.com'): Payment
    {
        $referred = TenantProvisioner::createOwner('Owner Diajak', $email, 'google-'.md5($email), $inviter->tenant);

        $this->actingAs($referred)
            ->postJson($this->api('/payments', $referred), ['days' => 365, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()])
            ->assertCreated();

        $payment = Payment::where('tenant_id', $referred->tenant_id)->where('status', 'pending')->firstOrFail();
        $this->actingAs($this->superAdmin())->post(route('admin.payments.approve', $payment))->assertRedirect();

        return $payment->fresh();
    }

    public function test_join_link_remembers_inviter_and_counts_visit(): void
    {
        $inviter = $this->createOwner();
        $code = Affiliate::codeFor($inviter->tenant);

        $this->get(route('join', ['code' => $code]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('referral_tenant_id', $inviter->tenant_id);

        $this->assertSame(1, $inviter->tenant->fresh()->referral_visits);

        // Halaman login memberi tahu tamu siapa yang mengundang.
        $this->get(route('login'))->assertOk()->assertSee('Anda diundang oleh');

        // Membuka ulang link yang sama dalam satu sesi tidak menambah hitungan.
        $this->get(route('join', ['code' => $code]));
        $this->assertSame(1, $inviter->tenant->fresh()->referral_visits);
    }

    public function test_join_alias_and_lowercase_code_work(): void
    {
        $inviter = $this->createOwner();
        $code = Affiliate::codeFor($inviter->tenant);

        // Bentuk /join=KODE persis seperti contoh link yang dibagikan.
        $this->get('http://'.config('catamu.domains.main').'/join='.$code)
            ->assertRedirect(route('login'))
            ->assertSessionHas('referral_tenant_id', $inviter->tenant_id);

        $this->flushSession();
        $this->get(route('join', ['code' => strtolower($code)]))
            ->assertSessionHas('referral_tenant_id', $inviter->tenant_id);
    }

    public function test_unknown_join_code_is_rejected(): void
    {
        $this->get(route('join', ['code' => 'TIDAKADA']))
            ->assertRedirect(route('login'))
            ->assertSessionMissing('referral_tenant_id')
            ->assertSessionHas('error');
    }

    public function test_owner_customizes_referral_code(): void
    {
        $owner = $this->createOwner();
        $other = $this->createOwner('lain@example.com');
        Affiliate::codeFor($other->tenant);
        $other->tenant->forceFill(['referral_code' => 'KANTORLAIN'])->save();

        $this->actingAs($owner)->putJson($this->api('/affiliate/code', $owner), ['code' => 'kantor-saya'])
            ->assertOk()
            ->assertJsonPath('affiliate.code', 'KANTOR-SAYA')
            ->assertJsonPath('affiliate.link', route('join', ['code' => 'KANTOR-SAYA']));

        $this->putJson($this->api('/affiliate/code', $owner), ['code' => 'kantorlain'])
            ->assertStatus(422)->assertJsonPath('message', 'Kode ini sudah dipakai kantor lain. Coba kode yang lain.');

        $this->putJson($this->api('/affiliate/code', $owner), ['code' => 'a b'])->assertStatus(422);
        $this->putJson($this->api('/affiliate/code', $owner), ['code' => '-awal'])->assertStatus(422);

        $this->assertSame('KANTOR-SAYA', $owner->tenant->fresh()->referral_code);
    }

    public function test_commission_is_recorded_when_referred_office_pays(): void
    {
        $inviter = $this->createOwner();
        $payment = $this->referredOfficePays($inviter);

        $commission = ReferralCommission::firstOrFail();
        $this->assertSame($inviter->tenant_id, $commission->referrer_tenant_id);
        $this->assertSame($payment->id, $commission->payment_id);
        $this->assertSame(20, (int) $commission->rate);
        $this->assertSame(19800, (int) $commission->amount);

        // Kantor pengajak diberi tahu, dan saldonya siap dicairkan.
        $this->assertSame('affiliate', $inviter->tenant->appNotifications()->latest('id')->first()->event);
        $this->assertSame(19800, Affiliate::balance($inviter->tenant->fresh())['available']);

        // Menyetujui ulang pembayaran yang sama tidak menggandakan komisi.
        $this->actingAs($this->superAdmin())->post(route('admin.payments.approve', $payment));
        $this->assertSame(1, ReferralCommission::count());
    }

    public function test_referral_state_is_visible_to_owner_only(): void
    {
        $inviter = $this->createOwner();
        $this->referredOfficePays($inviter);

        $this->actingAs($inviter)->getJson($this->api('/state', $inviter))
            ->assertOk()
            ->assertJsonPath('affiliate.signups', 1)
            ->assertJsonPath('affiliate.subscribed', 1)
            ->assertJsonPath('affiliate.balance.earned', 19800);

        // Menu Afiliasi hanya dirender di halaman aplikasi milik Owner.
        $this->get($inviter->tenant->appUrl())->assertOk()
            ->assertSee('settingsAffiliateView', false)
            ->assertSee('Rekening Pencairan', false);

        $member = $this->createTeamMember($inviter->tenant, 'Admin', ['guests' => true, 'settings' => true]);
        $this->actingAs($member)->getJson($this->api('/state', $member))
            ->assertOk()
            ->assertJsonPath('affiliate', null);
        $this->get($member->tenant->appUrl())->assertOk()->assertDontSee('settingsAffiliateView', false);

        $this->putJson($this->api('/affiliate/code', $member), ['code' => 'CURANG'])->assertForbidden();
        $this->putJson($this->api('/affiliate/account', $member), ['bank' => 'BCA', 'number' => '1', 'name' => 'X'])->assertForbidden();
        $this->postJson($this->api('/affiliate/payouts', $member), ['amount' => 1000])->assertForbidden();
    }

    public function test_payout_requires_account_minimum_and_enough_balance(): void
    {
        PlatformSetting::put('affiliate_min_payout', 10000);
        $inviter = $this->createOwner();
        $this->referredOfficePays($inviter);
        $this->actingAs($inviter);

        // Rekening belum diisi.
        $this->postJson($this->api('/affiliate/payouts', $inviter), ['amount' => 19800])
            ->assertStatus(422)->assertJsonPath('message', 'Lengkapi rekening pencairan terlebih dahulu.');

        $this->putJson($this->api('/affiliate/account', $inviter), ['bank' => 'BCA', 'number' => '1234567890', 'name' => 'Budi'])
            ->assertOk()->assertJsonPath('affiliate.account.bank', 'BCA');

        $this->postJson($this->api('/affiliate/payouts', $inviter), ['amount' => 5000])
            ->assertStatus(422)->assertJsonPath('message', 'Pencairan minimal Rp10.000.');

        $this->postJson($this->api('/affiliate/payouts', $inviter), ['amount' => 50000])
            ->assertStatus(422)->assertJsonPath('message', 'Saldo komisi yang bisa dicairkan hanya Rp19.800.');

        $this->postJson($this->api('/affiliate/payouts', $inviter), ['amount' => 19800])
            ->assertCreated()->assertJsonPath('affiliate.balance.available', 0)
            ->assertJsonPath('affiliate.balance.onHold', 19800);

        // Saldo tertahan selama pengajuan pertama belum diproses.
        $this->postJson($this->api('/affiliate/payouts', $inviter), ['amount' => 19800])
            ->assertStatus(422)->assertJsonPath('message', 'Masih ada pengajuan pencairan yang menunggu diproses.');
    }

    public function test_admin_approves_and_rejects_payout(): void
    {
        PlatformSetting::put('affiliate_min_payout', 10000);
        $inviter = $this->createOwner();
        $this->referredOfficePays($inviter);
        $this->actingAs($inviter);
        $this->putJson($this->api('/affiliate/account', $inviter), ['bank' => 'BCA', 'number' => '1234567890', 'name' => 'Budi']);
        $this->postJson($this->api('/affiliate/payouts', $inviter), ['amount' => 19800])->assertCreated();

        $payout = PayoutRequest::firstOrFail();
        $this->post(route('admin.affiliates.reject', $payout))->assertForbidden();

        $admin = $this->superAdmin();
        $this->actingAs($admin)->post(route('admin.affiliates.reject', $payout), ['note' => 'Rekening tidak cocok'])->assertRedirect();

        // Ditolak: saldo kembali tersedia dan kantor diberi tahu alasannya.
        $this->assertSame(19800, Affiliate::balance($inviter->tenant->fresh())['available']);
        $this->assertStringContainsString('Rekening tidak cocok', $inviter->tenant->appNotifications()->latest('id')->first()->message);

        $this->actingAs($inviter)->postJson($this->api('/affiliate/payouts', $inviter), ['amount' => 19800])->assertCreated();
        $second = PayoutRequest::where('status', PayoutRequest::STATUS_PENDING)->firstOrFail();
        $this->actingAs($admin)->post(route('admin.affiliates.approve', $second))->assertRedirect();

        // Disetujui: saldo terpakai habis, tidak bisa dicairkan dua kali.
        $balance = Affiliate::balance($inviter->tenant->fresh());
        $this->assertSame(0, $balance['available']);
        $this->assertSame(19800, $balance['paid']);
        $this->post(route('admin.affiliates.approve', $second))->assertRedirect();
        $this->assertSame(19800, Affiliate::balance($inviter->tenant->fresh())['paid']);
    }

    public function test_super_admin_sets_commission_rate_and_sees_affiliate_page(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->post(route('admin.settings.affiliate'), ['affiliate_rate' => 30, 'affiliate_min_payout' => 25000])
            ->assertRedirect();
        $this->assertSame(30, Affiliate::rate());
        $this->assertSame(25000, Affiliate::minPayout());

        // Persentase baru dipakai komisi berikutnya.
        $inviter = $this->createOwner();
        $this->referredOfficePays($inviter);
        $this->assertSame(29700, (int) ReferralCommission::firstOrFail()->amount);

        $this->actingAs($admin)->get(route('admin.affiliates'))->assertOk()
            ->assertSee('Kantor dari referral')
            ->assertSee('Kantor pengajak teratas');
        $this->get(route('admin.settings'))->assertOk()->assertSee('Program afiliasi');
    }

    public function test_office_without_inviter_produces_no_commission(): void
    {
        $owner = $this->createOwner();
        $this->actingAs($owner)->postJson($this->api('/payments', $owner), ['days' => 365, 'planName' => '1 Tahun', 'proof' => $this->pngDataUrl()]);
        $this->actingAs($this->superAdmin())->post(route('admin.payments.approve', Payment::firstOrFail()));

        $this->assertSame(0, ReferralCommission::count());
    }
}
