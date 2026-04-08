<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case Single = 'single';
    case Divorced = 'divorced';
    case Widowed = 'widowed';
    case Separated = 'separated';
}

