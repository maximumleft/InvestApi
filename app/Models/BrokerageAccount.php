<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrokerageAccount extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'user_id',
    ];
}
