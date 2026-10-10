<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EmploymentType: string implements HasLabel
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Temporary = 'temporary';
    case Student = 'student';
    case Internship = 'internship';
    case Freelance = 'freelance';

    public function getLabel(): string
    {
        return __('vacancies.employment_types.'.$this->value);
    }
}
