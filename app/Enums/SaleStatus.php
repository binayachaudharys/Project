<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Draft = 'draft';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Void = 'void';
}
