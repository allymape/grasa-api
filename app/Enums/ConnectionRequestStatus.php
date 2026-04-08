<?php

namespace App\Enums;

enum ConnectionRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case PaymentPending = 'payment_pending';
    case PartiallyPaid = 'partially_paid';
    case Connected = 'connected';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Pending->value,
            self::Accepted->value,
            self::PaymentPending->value,
            self::PartiallyPaid->value,
            self::Connected->value,
        ];
    }

    /**
     * @return list<string>
     */
    public static function awaitingPaymentValues(): array
    {
        return [
            self::PaymentPending->value,
            self::PartiallyPaid->value,
        ];
    }
}
