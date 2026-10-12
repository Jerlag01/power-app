<?php

namespace App\Filament\Resources\BlockedIps\Schemas;

use App\Models\BlockedIp;
use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlockedIpForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('ip')
                            ->label(__('blocked_ips.fields.ip'))
                            ->helperText(__('blocked_ips.help.ip', ['own' => request()->ip()]))
                            ->placeholder('203.0.113.7')
                            ->required()
                            ->maxLength(64)
                            ->rule(fn (?BlockedIp $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                if ($error = BlockedIp::validationError((string) $value)) {
                                    $fail($error);

                                    return;
                                }

                                // Compared after normalising, so 10.0.0.7/24 and 10.0.0.0/24 count as the same rule.
                                $exists = BlockedIp::where('ip', BlockedIp::normalize((string) $value))
                                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                    ->exists();

                                if ($exists) {
                                    $fail(__('blocked_ips.validation.exists'));
                                }
                            })
                            ->dehydrateStateUsing(fn (?string $state): string => BlockedIp::normalize((string) $state))
                            ->columnSpanFull(),
                        Textarea::make('reason')
                            ->label(__('blocked_ips.fields.reason'))
                            ->helperText(__('blocked_ips.help.reason'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(['lg' => 2]),

                Section::make(__('blocked_ips.sections.status'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('blocked_ips.fields.is_active'))
                            ->helperText(__('blocked_ips.help.is_active'))
                            ->default(true),
                        DateTimePicker::make('expires_at')
                            ->label(__('blocked_ips.fields.expires_at'))
                            ->helperText(__('blocked_ips.help.expires_at'))
                            ->seconds(false)
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),
                        TextEntry::make('hits')
                            ->label(__('blocked_ips.fields.hits'))
                            ->state(fn (?BlockedIp $record): string => $record
                                ? $record->hits.($record->last_hit_at ? ' · '.__('blocked_ips.last_hit', ['time' => $record->last_hit_at->format('d/m/Y H:i')]) : '')
                                : '0')
                            ->hiddenOn('create'),
                        TextEntry::make('creator.name')
                            ->label(__('blocked_ips.fields.created_by'))
                            ->placeholder('-')
                            ->hiddenOn('create'),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }
}
