<?php

namespace App\Enums;

enum StockReason: string
{
    case Sale = 'sale';
    case ManualAdjust = 'manual_adjust';
    case VoidRestore = 'void_restore';
}
