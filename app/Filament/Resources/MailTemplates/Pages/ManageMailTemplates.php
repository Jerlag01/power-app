<?php

namespace App\Filament\Resources\MailTemplates\Pages;

use App\Filament\Resources\MailTemplates\MailTemplateResource;
use App\Support\MailTemplates;
use Filament\Resources\Pages\ManageRecords;

class ManageMailTemplates extends ManageRecords
{
    protected static string $resource = MailTemplateResource::class;

    /**
     * A new mail or language gets its templates (the defaults) as soon as the list is opened.
     */
    public function mount(): void
    {
        MailTemplates::sync();

        parent::mount();
    }
}
