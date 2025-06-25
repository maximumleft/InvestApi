<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Positions extends Model
{
    protected $fillable = [
        'figi',
        'ticker',
        'quantity',
        'average_price',
        'expected_yield',
        'current_price',
        'currency',
    ];
}
