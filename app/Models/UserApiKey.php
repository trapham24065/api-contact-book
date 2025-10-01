<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserApiKey extends Model
{

    use HasFactory;

    protected $fillable
        = [
            'user_id',
            'api_key',
            'status',
            'expires_at',
        ];

    // Relationship: An API key belongs to a User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

}
