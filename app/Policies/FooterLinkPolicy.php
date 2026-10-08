<?php

namespace App\Policies;

class FooterLinkPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'footer_links';
    }
}
