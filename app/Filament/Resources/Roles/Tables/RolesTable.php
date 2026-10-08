<?php

namespace App\Filament\Resources\Roles\Tables;

use App\Models\Role;
use App\Support\Permissions;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        $total = collect(Permissions::all())->flatten()->count();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('users'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (Role $record): ?string => $record->description)
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_super')
                    ->label(__('admin.roles.is_super'))
                    ->boolean(),
                TextColumn::make('permissions')
                    ->label(__('admin.roles.permissions'))
                    ->state(fn (Role $record): string => $record->permissionCount()." / {$total}")
                    ->badge()
                    ->color('gray'),
                TextColumn::make('users_count')
                    ->label(__('admin.resources.user.plural'))
                    ->numeric()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                // The policy blocks deleting super roles and roles that still have users.
                DeleteAction::make(),
            ]);
    }
}
