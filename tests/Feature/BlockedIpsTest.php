<?php

namespace Tests\Feature;

use App\Filament\Resources\BlockedIps\BlockedIpResource;
use App\Filament\Resources\BlockedIps\Pages\CreateBlockedIp;
use App\Filament\Resources\BlockedIps\Pages\EditBlockedIp;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Mail\ContactMessageReceived;
use App\Models\BlockedIp;
use App\Models\ContactMessage;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BlockedIpsTest extends TestCase
{
    use RefreshDatabase;

    private function fromIp(string $ip): static
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip]);
    }

    public function test_contact_form_stores_the_ip_silently_and_keeps_it_out_of_the_mail(): void
    {
        Mail::fake();
        app(Settings::class)->save('contact', [...settings('contact'), 'notify_email' => 'info@example.com']);

        $this->fromIp('203.0.113.7')->get('/nl/contact')->assertOk()->assertDontSee('203.0.113.7');

        $this->fromIp('203.0.113.7')->post('/nl/contact', [
            'name' => 'Jan', 'email' => 'jan@example.com', 'subject' => 'Offerte', 'message' => 'Hallo', 'privacy' => '1',
        ])->assertRedirect();

        $this->assertSame('203.0.113.7', ContactMessage::sole()->ip_address);
        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail): bool {
            $mail->assertDontSeeInHtml('203.0.113.7');
            $mail->assertDontSeeInText('203.0.113.7');

            return true;
        });
    }

    public function test_admin_sees_the_ip_on_the_message_and_can_block_it(): void
    {
        $message = ContactMessage::factory()->create(['ip_address' => '198.51.100.23']);
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertSee('198.51.100.23')
            ->assertActionVisible('blockIp')
            ->assertActionHasUrl('blockIp', BlockedIpResource::getUrl('create', [
                'ip' => '198.51.100.23',
                'reason' => 'Contactbericht van '.$message->name.' <'.$message->email.'> op '.$message->created_at->format('d/m/Y H:i').': "'.$message->subject.'"',
            ]));

        BlockedIp::factory()->create(['ip' => '198.51.100.0/24']);

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])->assertActionHidden('blockIp');
    }

    public function test_blocked_visitors_get_the_410_page_everywhere_on_the_public_site(): void
    {
        BlockedIp::factory()->create(['ip' => '203.0.113.7']);

        foreach (['/', '/nl', '/nl/contact', '/en/projects'] as $url) {
            $this->fromIp('203.0.113.7')->get($url)->assertStatus(410);
        }

        $this->fromIp('203.0.113.7')->get('/en')
            ->assertStatus(410)
            ->assertSee('Access denied')
            ->assertSee('Due to your behaviour on our website, your access to our site has been limited.')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertDontSee('/en/contact');
        $this->fromIp('203.0.113.7')->get('/nl')->assertSee('Toegang geweigerd');
        $this->fromIp('203.0.113.7')->get('/fr')->assertSee('Accès refusé');

        $this->fromIp('203.0.113.7')->post('/nl/contact', ['name' => 'x'])->assertStatus(410);
        $this->assertSame(0, ContactMessage::count());

        $this->fromIp('203.0.113.8')->get('/nl')->assertOk();
        $this->assertSame(8, BlockedIp::sole()->hits);
        $this->assertNotNull(BlockedIp::sole()->last_hit_at);
    }

    public function test_the_admin_panel_stays_reachable_from_a_blocked_ip(): void
    {
        BlockedIp::factory()->create(['ip' => '203.0.113.7']);

        $this->fromIp('203.0.113.7')->get('/admin/login')->assertOk();
    }

    public function test_ranges_ipv6_expiry_and_switching_off(): void
    {
        BlockedIp::factory()->create(['ip' => '198.51.100.0/24']);
        BlockedIp::factory()->create(['ip' => '2001:db8::/48']);
        BlockedIp::factory()->expired()->create(['ip' => '192.0.2.1']);
        $inactive = BlockedIp::factory()->inactive()->create(['ip' => '192.0.2.2']);
        $later = BlockedIp::factory()->create(['ip' => '192.0.2.3', 'expires_at' => now()->addHour()]);

        $this->fromIp('198.51.100.250')->get('/nl')->assertStatus(410);
        $this->fromIp('198.51.101.1')->get('/nl')->assertOk();
        $this->fromIp('2001:db8:0:ffff::1')->get('/nl')->assertStatus(410);
        $this->fromIp('2001:db8:1::1')->get('/nl')->assertOk();
        $this->fromIp('192.0.2.1')->get('/nl')->assertOk();
        $this->fromIp('192.0.2.2')->get('/nl')->assertOk();
        $this->fromIp('192.0.2.3')->get('/nl')->assertStatus(410);

        // Changes reach the cached rule list right away.
        $inactive->update(['is_active' => true]);
        $this->fromIp('192.0.2.2')->get('/nl')->assertStatus(410);

        $later->delete();
        $this->fromIp('192.0.2.3')->get('/nl')->assertOk();

        $this->travel(2)->hours();
        $this->fromIp('198.51.100.250')->get('/nl')->assertStatus(410);
    }

    public function test_admin_adds_edits_and_removes_a_block(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get('/admin/blocked-ips/create?ip=203.0.113.7&reason=Spam')->assertOk();

        Livewire::withQueryParams(['ip' => '203.0.113.9', 'reason' => 'Spam via contactformulier'])
            ->test(CreateBlockedIp::class)
            ->assertSchemaStateSet(['ip' => '203.0.113.9', 'reason' => 'Spam via contactformulier'])
            ->call('create')
            ->assertHasNoFormErrors();

        $blocked = BlockedIp::sole();
        $this->assertSame($admin->id, $blocked->user_id);
        $this->fromIp('203.0.113.9')->get('/nl')->assertStatus(410);

        Livewire::test(EditBlockedIp::class, ['record' => $blocked->getRouteKey()])
            ->fillForm(['ip' => '203.0.113.77/24', 'expires_at' => now()->addDay()->format('Y-m-d H:i')])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('203.0.113.0/24', $blocked->refresh()->ip);
        $this->fromIp('203.0.113.200')->get('/nl')->assertStatus(410);

        Livewire::test(EditBlockedIp::class, ['record' => $blocked->getRouteKey()])->callAction('delete');
        $this->assertSame(0, BlockedIp::count());
        $this->fromIp('203.0.113.200')->get('/nl')->assertOk();
    }

    public function test_invalid_too_wide_and_duplicate_entries_are_refused(): void
    {
        BlockedIp::factory()->create(['ip' => '10.20.0.0/16']);
        $this->actingAs(User::factory()->admin()->create());

        foreach (['not-an-ip', '300.1.1.1', '10.0.0.0/8', '0.0.0.0/0', '2001:db8::/16', '10.20.5.5/16', '1.2.3.4/33'] as $value) {
            Livewire::test(CreateBlockedIp::class)
                ->fillForm(['ip' => $value])
                ->call('create')
                ->assertHasFormErrors(['ip']);
        }

        $this->assertSame(1, BlockedIp::count());
    }

    public function test_blocked_ips_follow_role_permissions(): void
    {
        $this->actingAs(User::factory()->withPermissions(['contact_messages' => ['view']])->create());
        $this->get('/admin/blocked-ips')->assertForbidden();

        $message = ContactMessage::factory()->create(['ip_address' => '198.51.100.23']);
        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])->assertActionHidden('blockIp');

        $this->actingAs(User::factory()->withPermissions(['blocked_ips' => ['view']])->create());
        $this->get('/admin/blocked-ips')->assertOk();
        $this->get('/admin/blocked-ips/create')->assertForbidden();
    }

    public function test_addresses_and_ranges_are_normalised(): void
    {
        $this->assertSame('203.0.113.0/24', BlockedIp::normalize(' 203.0.113.77/24 '));
        $this->assertSame('2001:db8::/48', BlockedIp::normalize('2001:DB8:0:0:0:0:0:1/48'));
        $this->assertSame('2001:db8::1', BlockedIp::normalize('2001:0DB8::0001'));
        $this->assertSame('203.0.113.7', BlockedIp::normalize('203.0.113.7'));
    }
}
