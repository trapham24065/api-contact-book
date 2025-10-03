<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactAttribute extends Model
{

    use HasFactory;

    protected $primaryKey = 'attribute_id';

    protected $fillable = ['contact_id', 'attr_key', 'attr_value'];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'contact_id');
    }

}
