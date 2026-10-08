<?php

namespace App\Policies;

class ExpertisePolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'expertises';
    }
}
