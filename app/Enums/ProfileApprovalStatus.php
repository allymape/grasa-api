<?php

namespace App\Enums;

enum ProfileApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

