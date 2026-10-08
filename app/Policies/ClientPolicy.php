<?php

namespace App\Policies;

class ClientPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'clients';
    }
}
