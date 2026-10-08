<?php

namespace App\Policies;

class ProjectPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'projects';
    }
}
