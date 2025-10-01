<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{

    use HasFactory;

    protected $table = 'password_resets';

    public $timestamps = false;

    protected $fillable
        = [
            'user_id',
            'email',
            'token_hash',
            'expires_at',
            'used_at',
            'request_ip',
            'user_agent',
            'created_at',
        ];

}
