<?php

namespace App\Policies;

class JobApplicationPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'job_applications';
    }
}
