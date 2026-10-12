<?php

namespace App\Filament\Resources\BlockedIps\Pages;

use App\Filament\Resources\BlockedIps\BlockedIpResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlockedIp extends CreateRecord
{
    protected static string $resource = BlockedIpResource::class;

    /**
     * Pre-fill from a link such as ?ip=203.0.113.7&reason=… (used by "Block IP" on a contact message).
     */
    protected function fillForm(): void
    {
        parent::fillForm();

        $this->form->fill([
            ...$this->form->getRawState(),
            'ip' => request()->query('ip'),
            'reason' => request()->query('reason'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'user_id' => auth()->id()];
    }
}
