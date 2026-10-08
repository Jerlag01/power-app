<?php

namespace App\Filament\Resources\Expertises\Pages;

use App\Filament\Resources\Expertises\ExpertiseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpertises extends ListRecords
{
    protected static string $resource = ExpertiseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
