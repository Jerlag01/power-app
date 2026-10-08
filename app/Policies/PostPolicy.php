<?php

namespace App\Policies;

class PostPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'posts';
    }
}
