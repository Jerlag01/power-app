<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    /**
     * Reminds editors when the page is switched off, so they know why it is not on the website.
     */
    public function getSubheading(): string|Htmlable|null
    {
        return settings('general.show_who_is_who', true) ? null : __('admin.help.who_is_who_disabled');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
