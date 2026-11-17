<?php

namespace App\Policies;

class SeoTagPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'seo_tags';
    }
}
