<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = ['user_id', 'action', 'entity_type', 'entity_id', 'details', 'created_at'];

}
