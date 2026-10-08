<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('power:create-user
    {--name= : Full name}
    {--email= : E-mail address used to log in}
    {--role= : Role name, e.g. "Administrator" or "Editor"}
    {--locale= : Language of the admin panel: nl, fr or en}')]
#[Description('Create a user for the admin panel, e.g. the first administrator on a new install')]
class CreateUser extends Command
{
    public function handle(): int
    {
        $roles = Role::query()->orderByDesc('is_super')->orderBy('name')->pluck('name', 'id');

        if ($roles->isEmpty()) {
            $this->components->error('No roles found. Run "php artisan migrate" first.');

            return self::FAILURE;
        }

        $roleId = $this->option('role') !== null
            ? $roles->search($this->option('role'))
            : select('Role', $roles->all(), default: $roles->keys()->first());

        // The password is always asked for, so it never ends up in the shell history.
        $data = [
            'name' => $this->option('name') ?? text('Name', required: true),
            'email' => $this->option('email') ?? text('E-mail', required: true),
            'password' => password('Password', required: true, hint: 'At least 8 characters'),
            'role_id' => $roleId === false ? null : $roleId,
            'locale' => $this->option('locale') ?? select('Language', config('app.locales'), default: config('app.locale')),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'locale' => ['required', 'in:'.implode(',', array_keys(config('app.locales')))],
        ], [
            'role_id.required' => 'Unknown role. Choose one of: '.$roles->implode(', ').'.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create($data);

        $this->components->info("Created {$user->email} with role \"{$user->role->name}\".");

        return self::SUCCESS;
    }
}
