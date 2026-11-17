<?php

namespace App\Filament\Resources\SeoTags\Pages;

use App\Filament\Resources\SeoTags\SeoTagResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoTag extends EditRecord
{
    protected static string $resource = SeoTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
