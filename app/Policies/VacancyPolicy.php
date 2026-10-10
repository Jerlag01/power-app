<?php

namespace App\Policies;

class VacancyPolicy extends AreaPolicy
{
    protected function area(): string
    {
        return 'vacancies';
    }
}
