<?php

namespace App\Policies;

class ContactMessagePolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'contact_messages';
    }
}
