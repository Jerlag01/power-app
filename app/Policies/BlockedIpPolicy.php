<?php

namespace App\Policies;

class BlockedIpPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'blocked_ips';
    }
}
