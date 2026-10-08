<?php

namespace Tests\Feature;

use App\Filament\Resources\FooterLinks\Pages\CreateFooterLink;
use App\Filament\Resources\FooterLinks\Pages\ListFooterLinks;
use App\Models\FooterLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FooterLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_custom_links_row_only_appears_when_there_are_visible_links(): void
    {
        $this->get('/nl')->assertOk()->assertDontSee('aria-label="Meer links"', escape: false);

        FooterLink::factory()->hidden()->create(['label' => ['nl' => 'Verborgen link']]);
        $this->get('/nl')->assertDontSee('aria-label="Meer links"', escape: false)->assertDontSee('Verborgen link');

        FooterLink::factory()->create(['label' => ['nl' => 'Algemene voorwaarden', 'fr' => 'Conditions générales'], 'url' => 'https://example.com/voorwaarden']);

        $this->get('/nl')
            ->assertSee('aria-label="Meer links"', escape: false)
            ->assertSee('Algemene voorwaarden')
            ->assertSee('href="https://example.com/voorwaarden"', escape: false)
            ->assertDontSee('Verborgen link');

        $this->get('/fr')->assertSee('Conditions générales');
    }

    public function test_several_links_are_shown_in_their_own_order(): void
    {
        FooterLink::factory()->create(['label' => ['nl' => 'Tweede'], 'sort_order' => 2]);
        FooterLink::factory()->create(['label' => ['nl' => 'Eerste'], 'sort_order' => 1, 'url' => '/nl/contact', 'open_in_new_tab' => true]);
        FooterLink::factory()->create(['label' => ['nl' => 'Derde'], 'sort_order' => 3, 'url' => 'mailto:info@example.be']);

        $this->get('/nl')
            ->assertSeeInOrder(['Eerste', 'Tweede', 'Derde'])
            ->assertSee('href="mailto:info@example.be"', escape: false);

        $html = $this->get('/nl')->getContent();
        $this->assertMatchesRegularExpression('/href="\/nl\/contact"[^>]*target="_blank" rel="noopener noreferrer"/', $html, 'A link set to open in a new tab gets target="_blank".');
        $this->assertDoesNotMatchRegularExpression('/href="mailto:info@example\.be"[^>]*target="_blank"/', $html);
    }

    public function test_a_missing_translation_falls_back_to_dutch(): void
    {
        FooterLink::factory()->create(['label' => ['nl' => 'Vacatures']]);

        $this->get('/en')->assertSee('Vacatures');
    }

    public function test_links_are_managed_in_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/admin/footer-links')->assertOk();

        Livewire::test(CreateFooterLink::class)
            ->fillForm([
                'label' => ['nl' => 'Vacatures', 'fr' => 'Emplois', 'en' => 'Jobs'],
                'url' => 'https://jobs.example.be',
                'open_in_new_tab' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $link = FooterLink::sole();
        $this->assertSame('Emplois', $link->translate('label', 'fr'));
        $this->assertTrue($link->open_in_new_tab);
        $this->assertTrue($link->is_visible);

        Livewire::test(ListFooterLinks::class)->assertCanSeeTableRecords([$link]);
    }

    public function test_only_safe_link_types_are_accepted(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['javascript:alert(1)', '//evil.example.com', 'ftp://example.com', 'just text', 'data:text/html,hi'] as $url) {
            Livewire::test(CreateFooterLink::class)
                ->fillForm(['label' => ['nl' => 'Test'], 'url' => $url])
                ->call('create')
                ->assertHasFormErrors(['url' => 'regex']);
        }

        foreach (['https://example.com/a?b=c', 'http://example.com', '/nl/contact', '/', 'mailto:info@example.be', 'tel:+32 4 123 45 67'] as $url) {
            Livewire::test(CreateFooterLink::class)
                ->fillForm(['label' => ['nl' => 'Test'], 'url' => $url])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertSame(6, FooterLink::count());
    }

    public function test_the_label_is_required_in_the_default_language(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateFooterLink::class)
            ->fillForm(['label' => ['fr' => 'Seulement français'], 'url' => 'https://example.com'])
            ->call('create')
            ->assertHasFormErrors(['label.nl' => 'required']);
    }

    public function test_users_without_the_footer_links_permission_cannot_manage_them(): void
    {
        $editor = User::factory()->withPermissions(['posts' => ['view']])->create();
        $viewer = User::factory()->withPermissions(['footer_links' => ['view']])->create();

        $this->actingAs($editor)->get('/admin/footer-links')->assertForbidden();
        $this->actingAs($viewer)->get('/admin/footer-links')->assertOk();
        $this->actingAs($viewer)->get('/admin/footer-links/create')->assertForbidden();
    }
}
