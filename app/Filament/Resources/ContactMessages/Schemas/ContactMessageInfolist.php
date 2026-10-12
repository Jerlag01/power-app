<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use App\Models\BlockedIp;
use App\Models\ContactMessage;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make(__('admin.fields.message'))
                    ->schema([
                        TextEntry::make('subject')
                            ->label(__('admin.fields.subject'))
                            ->weight('bold'),
                        TextEntry::make('message')
                            ->hiddenLabel()
                            ->prose()
                            ->formatStateUsing(fn (string $state): string => nl2br(e($state)))
                            ->html(),
                    ])
                    ->columnSpan(['lg' => 2]),
                Section::make(__('admin.sections.sender'))
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
                        TextEntry::make('ip_address')
                            ->label(__('blocked_ips.fields.ip_address'))
                            ->placeholder('-')
                            ->fontFamily(FontFamily::Mono)
                            ->copyable()
                            ->badge(fn (ContactMessage $record): bool => BlockedIp::matching($record->ip_address) !== null)
                            ->color(fn (ContactMessage $record): ?string => BlockedIp::matching($record->ip_address) !== null ? 'danger' : null)
                            ->helperText(fn (ContactMessage $record): ?string => BlockedIp::matching($record->ip_address) !== null ? __('blocked_ips.status.blocked') : null),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
