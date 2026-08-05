<?php

namespace App\Enums;

enum BookableType: string
{
    case Service = 'service';
    case Package = 'package';
}
