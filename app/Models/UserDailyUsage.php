<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDailyUsage extends Model
{

    public $timestamps = false;

    protected $fillable = ['user_id', 'usage_date', 'request_count'];

}
