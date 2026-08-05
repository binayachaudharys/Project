<?php

namespace App\Enums;

enum ItemType: string
{
    case Service = 'service';
    case Package = 'package';
    case Product = 'product';
}
