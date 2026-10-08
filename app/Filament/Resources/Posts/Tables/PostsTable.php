<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('author'))
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->state(fn (Post $record): ?string => $record->translate('title'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('title', 'like', "%{$search}%"))
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->state(fn (Post $record): string => match (true) {
                        $record->published_at === null => 'draft',
                        $record->published_at->isFuture() => 'scheduled',
                        default => 'published',
                    })
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __("admin.status.{$state}"))
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'scheduled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('published_at')
                    ->label(__('admin.fields.published_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('author.name')
                    ->label(__('admin.fields.author'))
                    ->toggleable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                TernaryFilter::make('published')
                    ->label(__('admin.fields.status'))
                    ->trueLabel(__('admin.status.published'))
                    ->falseLabel(__('admin.status.not_published'))
                    ->queries(
                        true: fn (Builder $query) => $query->published(),
                        false: fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '>', now())),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                Action::make('viewOnSite')
                    ->label(__('admin.actions.view_on_site'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Post $record): string => route('posts.show', ['locale' => app()->getLocale(), 'post' => $record]))
                    ->openUrlInNewTab()
                    ->visible(fn (Post $record): bool => $record->isPublished()),
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
