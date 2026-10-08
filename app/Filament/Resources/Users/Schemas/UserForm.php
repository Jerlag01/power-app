<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Select::make('role_id')
                            ->label(__('admin.fields.role'))
                            ->relationship(
                                'role',
                                'name',
                                // Only super administrators can hand out super roles.
                                fn (Builder $query) => auth()->user()?->isSuperAdmin() ? $query : $query->where('is_super', false),
                            )
                            ->helperText(__('admin.help.role'))
                            ->required()
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                if (! auth()->user()?->isSuperAdmin() && Role::whereKey($value)->where('is_super', true)->exists()) {
                                    $fail(__('admin.roles.cannot_assign_super'));
                                }
                            })
                            ->preload()
                            ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false),
                        Select::make('locale')
                            ->label(__('admin.fields.admin_language'))
                            ->options(config('app.locales'))
                            ->default(config('app.locale'))
                            ->required(),
                        TextInput::make('password')
                            ->label(__('admin.fields.password'))
                            ->password()
                            ->revealable()
                            ->rule(Password::default())
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(fn (string $operation): ?string => $operation === 'edit' ? __('admin.help.password') : null),
                    ]),
            ]);
    }
}
