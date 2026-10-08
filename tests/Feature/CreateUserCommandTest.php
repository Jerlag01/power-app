<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_administrator_interactively(): void
    {
        $administrator = Role::where('is_super', true)->sole();

        $this->artisan('power:create-user')
            ->expectsChoice('Role', $administrator->id, Role::query()->orderByDesc('is_super')->orderBy('name')->pluck('name', 'id')->all())
            ->expectsQuestion('Name', 'Jeroen Lagaet')
            ->expectsQuestion('E-mail', 'jeroen@example.be')
            ->expectsQuestion('Password', 'a-Strong-password-123')
            ->expectsChoice('Language', 'nl', config('app.locales'))
            ->assertSuccessful();

        $user = User::where('email', 'jeroen@example.be')->sole();
        $this->assertTrue($user->role->is($administrator));
        $this->assertTrue(Hash::check('a-Strong-password-123', $user->password));
        $this->assertSame('nl', $user->locale);

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_options_skip_the_questions_except_the_password(): void
    {
        $this->artisan('power:create-user', ['--name' => 'Eva Editor', '--email' => 'eva@example.be', '--role' => 'Editor', '--locale' => 'fr'])
            ->expectsQuestion('Password', 'a-Strong-password-123')
            ->assertSuccessful();

        $user = User::where('email', 'eva@example.be')->sole();
        $this->assertSame('Editor', $user->role->name);
        $this->assertSame('fr', $user->locale);
    }

    public function test_it_refuses_an_unknown_role_or_a_taken_email(): void
    {
        $this->artisan('power:create-user', ['--name' => 'X', '--email' => 'x@example.be', '--role' => 'Nope', '--locale' => 'nl'])
            ->expectsQuestion('Password', 'a-Strong-password-123')
            ->assertFailed();

        User::factory()->create(['email' => 'taken@example.be']);

        $this->artisan('power:create-user', ['--name' => 'Y', '--email' => 'taken@example.be', '--role' => 'Editor', '--locale' => 'nl'])
            ->expectsQuestion('Password', 'a-Strong-password-123')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'x@example.be']);
    }
}
