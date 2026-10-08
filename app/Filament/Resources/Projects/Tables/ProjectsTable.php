<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Expertise;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('expertise'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->state(fn (Project $record): ?string => $record->translate('title'))
                    ->description(fn (Project $record): string => $record->location)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('title', 'like', "%{$search}%")->orWhere('location', 'like', "%{$search}%"))
                    ->wrap(),
                TextColumn::make('expertise')
                    ->label(__('admin.resources.expertise.singular'))
                    ->state(fn (Project $record): ?string => $record->expertise?->translate('title'))
                    ->badge()
                    ->color('gray'),
                ToggleColumn::make('is_published')
                    ->label(__('admin.fields.is_published'))
                    ->disabled(fn (Project $record): bool => ! auth()->user()->can('update', $record)),
                ToggleColumn::make('is_featured')
                    ->label(__('admin.fields.is_featured'))
                    ->disabled(fn (Project $record): bool => ! auth()->user()->can('update', $record)),
            ])
            ->filters([
                SelectFilter::make('expertise_id')
                    ->label(__('admin.resources.expertise.singular'))
                    ->options(fn (): array => Expertise::ordered()->get()->mapWithKeys(fn (Expertise $expertise): array => [$expertise->id => $expertise->translate('title')])->all()),
                TernaryFilter::make('is_published')
                    ->label(__('admin.fields.is_published')),
            ])
            ->recordActions([
                Action::make('viewOnSite')
                    ->label(__('admin.actions.view_on_site'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Project $record): string => route('projects.show', ['locale' => app()->getLocale(), 'project' => $record]))
                    ->openUrlInNewTab()
                    ->visible(fn (Project $record): bool => $record->is_published),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
