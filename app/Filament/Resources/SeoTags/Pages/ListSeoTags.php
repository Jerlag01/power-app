<?php

namespace App\Filament\Resources\SeoTags\Pages;

use App\Filament\Resources\SeoTags\SeoTagResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeoTags extends ListRecords
{
    protected static string $resource = SeoTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
