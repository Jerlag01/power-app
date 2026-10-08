<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings as SettingsPage;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Client;
use App\Models\ContactMessage;
use App\Models\Employee;
use App\Models\Expertise;
use App\Models\FooterLink;
use App\Models\Post;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{class-string, string, string}>
     */
    public static function areas(): array
    {
        return [
            'posts' => [Post::class, 'posts', '/admin/posts'],
            'projects' => [Project::class, 'projects', '/admin/projects'],
            'expertises' => [Expertise::class, 'expertises', '/admin/expertises'],
            'clients' => [Client::class, 'clients', '/admin/clients'],
            'footer links' => [FooterLink::class, 'footer_links', '/admin/footer-links'],
            'who is who' => [Employee::class, 'employees', '/admin/who-is-who'],
            'contact messages' => [ContactMessage::class, 'contact_messages', '/admin/contact-messages'],
            'users' => [User::class, 'users', '/admin/users'],
            'roles' => [Role::class, 'roles', '/admin/roles'],
        ];
    }

    /**
     * @param  class-string<Model>  $model
     */
    #[DataProvider('areas')]
    public function test_each_ability_is_granted_only_by_its_own_permission(string $model, string $area, string $url): void
    {
        $record = $model::factory()->create();

        $none = User::factory()->withPermissions([])->create();
        $viewer = User::factory()->withPermissions([$area => ['view']])->create();
        $full = User::factory()->withPermissions([$area => ['view', 'create', 'update', 'delete']])->create();

        $this->assertFalse($none->can('viewAny', $model));
        $this->assertTrue($viewer->can('viewAny', $model));
        $this->assertFalse($viewer->can('update', $record));
        $this->assertFalse($viewer->can('delete', $record));
        $this->assertTrue($full->can('update', $record) || $area === 'contact_messages' || $area === 'roles');

        $this->actingAs($none)->get($url)->assertForbidden();
        $this->actingAs($viewer)->get($url)->assertOk();
    }

    public function test_a_role_only_sees_the_navigation_it_has_permission_for(): void
    {
        $this->actingAs(User::factory()->withPermissions(['posts' => ['view']])->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('/admin/posts', escape: false)
            ->assertDontSee('/admin/projects', escape: false)
            ->assertDontSee('/admin/settings', escape: false);
    }

    public function test_view_only_users_cannot_open_create_or_edit_pages(): void
    {
        $post = Post::factory()->create();
        $this->actingAs(User::factory()->withPermissions(['posts' => ['view']])->create());

        $this->get('/admin/posts/create')->assertForbidden();
        $this->get("/admin/posts/{$post->getRouteKey()}/edit")->assertForbidden();
    }

    public function test_settings_tabs_follow_permissions_and_only_permitted_groups_are_saved(): void
    {
        $this->actingAs(User::factory()->withPermissions(['settings' => ['contact']])->create());

        $this->get('/admin/settings')->assertOk();

        Livewire::test(SettingsPage::class)
            ->fillForm(['contact.phone' => '+32 2 111 11 11'])
            ->set('data.general.site_name', 'Gehackt')
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $settings->flush();
        $this->assertSame('+32 2 111 11 11', $settings->get('contact.phone'));
        $this->assertSame('Power Installation NV', $settings->get('general.site_name'));
    }

    public function test_users_without_settings_permissions_cannot_open_settings(): void
    {
        $this->actingAs(User::factory()->withPermissions(['posts' => ['view']])->create())
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_roles_can_be_created_with_permissions_and_unknown_permissions_are_dropped(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateRole::class)
            ->fillForm([
                'name' => 'Webredactie',
                'description' => 'Enkel nieuws',
                'permissions' => ['posts' => ['view', 'create']],
            ])
            ->set('data.permissions.unknown', ['view'])
            ->call('create')
            ->assertHasNoFormErrors();

        $role = Role::firstWhere('name', 'Webredactie');
        $this->assertSame(['posts' => ['view', 'create']], $role->permissions);
        $this->assertTrue($role->allows('posts.create'));
        $this->assertFalse($role->allows('posts.delete'));
    }

    public function test_unknown_abilities_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Ongeldig'])
            ->set('data.permissions.posts', ['view', 'hack'])
            ->call('create')
            ->assertHasFormErrors(['permissions.posts.1']);

        $this->assertDatabaseMissing('roles', ['name' => 'Ongeldig']);
    }

    public function test_role_permissions_can_be_edited(): void
    {
        $role = Role::factory()->withPermissions(['posts' => ['view']])->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(EditRole::class, ['record' => $role->getKey()])
            ->fillForm(['permissions' => ['posts' => ['view'], 'clients' => ['view', 'update']]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($role->fresh()->allows('clients.update'));
    }

    public function test_super_roles_and_roles_in_use_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $inUse = Role::factory()->create();
        User::factory()->create(['role_id' => $inUse->getKey()]);
        $unused = Role::factory()->create();

        $this->assertFalse($admin->can('delete', $admin->role));
        $this->assertFalse($admin->can('delete', $inUse));
        $this->assertTrue($admin->can('delete', $unused));
    }

    public function test_non_super_users_cannot_escalate_their_privileges(): void
    {
        $manager = User::factory()->withPermissions([
            'roles' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'],
        ])->create();
        $superUser = User::factory()->admin()->create();

        $this->assertFalse($manager->can('update', $manager->role), 'cannot edit own role');
        $this->assertFalse($manager->can('update', $superUser->role), 'cannot edit a super role');
        $this->assertFalse($manager->can('update', $superUser), 'cannot edit a super administrator');
        $this->assertFalse($manager->can('delete', $superUser), 'cannot delete a super administrator');
        $this->assertFalse($manager->can('delete', $manager), 'cannot delete themselves');

        $this->actingAs($manager);

        Livewire::test(CreateRole::class)
            ->fillForm(['name' => 'Stiekem'])
            ->set('data.is_super', true)
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertFalse(Role::firstWhere('name', 'Stiekem')->is_super);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Handlanger',
                'email' => 'handlanger@example.com',
                'role_id' => $superUser->role_id,
                'locale' => 'nl',
                'password' => 'Sterk-Wachtwoord-123',
            ])
            ->call('create')
            ->assertHasFormErrors(['role_id']);
        $this->assertDatabaseMissing('users', ['email' => 'handlanger@example.com']);
    }
}
