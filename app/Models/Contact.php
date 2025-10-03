<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{

    use HasFactory;

    protected $primaryKey = 'contact_id';

    protected $fillable = ['user_id', 'name', 'phone', 'email', 'note'];

    protected $casts
        = [
            'user_id' => 'integer',
        ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ContactAttribute::class, 'contact_id', 'contact_id');
    }

}
