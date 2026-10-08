<?php

namespace App\Policies;

class EmployeePolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'employees';
    }
}
