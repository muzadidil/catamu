<?php

namespace Tests\Feature;

use App\Models\Guest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_owner_registers_guest_with_photo_signature_and_notification(): void
    {
        $owner = $this->createOwner();
        $department = $owner->tenant->departments()->where('code', 'FIN')->first();

        $response = $this->actingAs($owner)->postJson($this->api('/guests'), $this->guestPayload([
            'departmentId' => (string) $department->id,
            'meet' => 'nama palsu',
            'photo' => $this->pngDataUrl(),
            'signature' => $this->pngDataUrl(),
        ]));

        $response->assertCreated()
            ->assertJsonPath('guest.name', 'Budi Santoso')
            ->assertJsonPath('guest.meet', 'Keuangan')
            ->assertJsonPath('guest.vehicle', 'N 1234 AB')
            ->assertJsonPath('guest.departmentId', (string) $department->id)
            ->assertJsonPath('notification.event', 'checkin')
            ->assertJsonPath('notification.stored', true);

        $guest = Guest::firstOrFail();
        Storage::disk('local')->assertExists($guest->photo_path);
        Storage::disk('local')->assertExists($guest->signature_path);
        $this->assertStringEndsWith('.jpg', $guest->photo_path);
        $this->assertStringEndsWith('.png', $guest->signature_path);

        $this->get($response->json('guest.photo'))->assertOk();
    }

    public function test_required_fields_follow_guest_field_settings(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)->postJson($this->api('/guests'), $this->guestPayload(['phone' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors(['phone' => 'Nomor HP / WhatsApp wajib diisi.']);

        $this->putJson($this->api('/settings/guest-fields'), ['guestFields' => [
            'phone' => ['visible' => false, 'required' => true],
            'photo' => ['visible' => true, 'required' => true],
        ]])->assertOk()->assertJsonPath('settings.guestFields.phone.required', false);

        $this->postJson($this->api('/guests'), $this->guestPayload(['phone' => '']))
            ->assertUnprocessable()->assertJsonValidationErrors(['photo' => 'Foto tamu wajib diisi.']);
    }

    public function test_update_keeps_existing_photo_when_url_is_sent_and_checkout_works(): void
    {
        $owner = $this->createOwner();
        $created = $this->actingAs($owner)->postJson($this->api('/guests'), $this->guestPayload(['photo' => $this->pngDataUrl()]))->json('guest');
        $photoPath = Guest::find($created['id'])->photo_path;

        $this->putJson($this->api("/guests/{$created['id']}"), $this->guestPayload(['purpose' => 'Revisi kontrak', 'photo' => $created['photo']]))
            ->assertOk()->assertJsonPath('guest.purpose', 'Revisi kontrak');
        $this->assertSame($photoPath, Guest::find($created['id'])->photo_path);

        $this->postJson($this->api("/guests/{$created['id']}/checkout"), ['note' => 'Selesai'])
            ->assertOk()->assertJsonPath('guest.checkoutNote', 'Selesai')->assertJsonPath('notification.event', 'checkout');
        $this->postJson($this->api("/guests/{$created['id']}/checkout"))->assertStatus(422);

        $this->deleteJson($this->api("/guests/{$created['id']}"))->assertOk();
        Storage::disk('local')->assertMissing($photoPath);
    }

    public function test_viewer_can_read_but_not_write(): void
    {
        $owner = $this->createOwner();
        $viewer = $this->createTeamMember($owner->tenant, 'Viewer', ['guests' => true, 'reports' => true]);

        $this->actingAs($viewer)->getJson($this->api('/state'))->assertOk()->assertJsonPath('actor.role', 'Viewer')->assertJsonPath('team', []);
        $this->postJson($this->api('/guests'), $this->guestPayload())
            ->assertForbidden()->assertJsonPath('message', 'Akses ditolak. Role Anda tidak diizinkan untuk menyimpan data tamu.');
        $this->putJson($this->api('/settings/application'), ['theme' => 'dark', 'dateFormat' => 'long', 'timeFormat' => '24'])->assertForbidden();
    }

    public function test_expired_subscription_is_read_only(): void
    {
        $owner = $this->createOwner();
        $owner->tenant->update(['trial_expires_at' => now()->subMinute()]);

        $this->actingAs($owner)->postJson($this->api('/guests'), $this->guestPayload())
            ->assertForbidden()->assertJsonPath('message', 'Masa trial/langganan telah berakhir. Aplikasi dalam mode baca saja.');
        $this->getJson($this->api('/state'))->assertOk();
    }

    public function test_offices_cannot_touch_each_others_guests(): void
    {
        $ownerA = $this->createOwner('a@example.com');
        $ownerB = $this->createOwner('b@example.com');
        $guest = $this->actingAs($ownerA)->postJson($this->api('/guests'), $this->guestPayload(['photo' => $this->pngDataUrl()]))->json('guest');

        $this->actingAs($ownerB);
        $this->putJson($this->api("/guests/{$guest['id']}"), $this->guestPayload())->assertNotFound();
        $this->deleteJson($this->api("/guests/{$guest['id']}"))->assertNotFound();
        $this->get($guest['photo'])->assertNotFound();
        $this->getJson($this->api('/state'))->assertJsonPath('guests', []);
    }

    public function test_notifications_respect_office_preferences(): void
    {
        $owner = $this->createOwner();
        $this->actingAs($owner)->putJson($this->api('/settings/notifications'), ['inApp' => false, 'browser' => true, 'checkIn' => true, 'checkOut' => false, 'updates' => true])->assertOk();

        $created = $this->postJson($this->api('/guests'), $this->guestPayload())
            ->assertJsonPath('notification.stored', false)->json('guest');
        $this->postJson($this->api("/guests/{$created['id']}/checkout"))->assertJsonPath('notification', null);
        $this->assertSame(0, $owner->tenant->appNotifications()->count());
    }

    public function test_image_payload_must_be_a_real_image(): void
    {
        $owner = $this->createOwner();

        $this->actingAs($owner)->postJson($this->api('/guests'), $this->guestPayload(['photo' => 'data:image/png;base64,'.base64_encode('<?php echo 1;')]))
            ->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertSame(0, Guest::count());
    }
}
