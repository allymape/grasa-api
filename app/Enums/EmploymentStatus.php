<?php

namespace App\Enums;

enum EmploymentStatus: string
{
    case Employed = 'employed';
    case SelfEmployed = 'self_employed';
    case Student = 'student';
    case Unemployed = 'unemployed';
    case Other = 'other';
}

