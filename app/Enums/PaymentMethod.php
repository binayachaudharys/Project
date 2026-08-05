<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Esewa = 'esewa';
    case Khalti = 'khalti';
    case Fonepay = 'fonepay';
}
