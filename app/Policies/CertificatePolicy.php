<?php

namespace App\Policies;

class CertificatePolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'certificates';
    }
}
