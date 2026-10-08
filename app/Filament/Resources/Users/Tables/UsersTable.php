<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('role'))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->description(fn (User $record): string => $record->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),
                TextColumn::make('role.name')
                    ->label(__('admin.fields.role'))
                    ->badge()
                    ->color(fn (User $record): string => $record->isSuperAdmin() ? 'primary' : 'gray')
                    ->placeholder(__('admin.roles.none')),
                TextColumn::make('locale')
                    ->label(__('admin.fields.admin_language'))
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role_id')
                    ->label(__('admin.fields.role'))
                    ->relationship('role', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
