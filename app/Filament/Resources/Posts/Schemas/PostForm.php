<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Support\TranslatableTabs;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    TranslatableTabs::make(fn (string $locale, bool $isDefault): array => [
                        TextInput::make("title.{$locale}")
                            ->label(__('admin.fields.title'))
                            ->required($isDefault)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, Set $set, string $operation) use ($isDefault): void {
                                if ($isDefault && $operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        Textarea::make("excerpt.{$locale}")
                            ->label(__('admin.fields.excerpt'))
                            ->helperText(__('admin.help.excerpt'))
                            ->rows(3)
                            ->maxLength(500),
                        RichEditor::make("body.{$locale}")
                            ->label(__('admin.fields.body'))
                            ->required($isDefault)
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('posts/attachments')
                            ->fileAttachmentsVisibility('public'),
                    ]),
                ])->columnSpan(['lg' => 2]),

                Section::make(__('admin.sections.publishing'))
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('admin.fields.slug'))
                            ->helperText(__('admin.help.slug'))
                            ->required()
                            ->alphaDash()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('published_at')
                            ->label(__('admin.fields.published_at'))
                            ->helperText(__('admin.help.published_at'))
                            ->seconds(false),
                        Select::make('user_id')
                            ->label(__('admin.fields.author'))
                            ->relationship('author', 'name')
                            ->default(fn (): ?int => auth()->id())
                            ->searchable()
                            ->preload(),
                        TranslatableTabs::image('image', 'posts'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
