<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Cash = 'cash';
    case Other = 'other';
}

