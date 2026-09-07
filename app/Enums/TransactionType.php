<?php

namespace App\Enums;

use App\Concerns\EnumsHelper;

enum TransactionType: string
{
    use EnumsHelper;

    case Buy = 'buy';
    case Sell = 'sell';
}
