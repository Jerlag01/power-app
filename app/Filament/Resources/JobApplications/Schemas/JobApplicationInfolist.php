<?php

namespace App\Filament\Resources\JobApplications\Schemas;

use App\Filament\Resources\Vacancies\VacancyResource;
use App\Models\JobApplication;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JobApplicationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(__('vacancies.fields.motivation'))
                    ->schema([
                        TextEntry::make('vacancy_title')
                            ->label(__('vacancies.resource.singular_title'))
                            ->weight('bold')
                            ->url(fn (JobApplication $record): ?string => $record->vacancy && auth()->user()->can('update', $record->vacancy)
                                ? VacancyResource::getUrl('edit', ['record' => $record->vacancy])
                                : null),
                        TextEntry::make('message')
                            ->hiddenLabel()
                            ->prose()
                            ->formatStateUsing(fn (string $state): string => nl2br(e($state)))
                            ->html(),
                        TextEntry::make('cv_name')
                            ->label(__('vacancies.fields.cv'))
                            ->icon('heroicon-o-paper-clip'),
                    ])
                    ->columnSpan(['lg' => 2]),
                Section::make(__('vacancies.sections.candidate'))
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('admin.fields.name')),
                        TextEntry::make('email')
                            ->label(__('admin.fields.email'))
                            ->url(fn (string $state): string => "mailto:{$state}")
                            ->copyable(),
                        TextEntry::make('phone')
                            ->label(__('admin.fields.phone'))
                            ->placeholder('-')
                            ->url(fn (?string $state): ?string => $state ? 'tel:'.preg_replace('/[^+\d]/', '', $state) : null),
                        TextEntry::make('locale')
                            ->label(__('admin.fields.language'))
                            ->formatStateUsing(fn (?string $state): string => config("app.locales.{$state}", '-'))
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->label(__('admin.fields.received_at'))
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
