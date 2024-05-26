<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationPriceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'variation_id',
        'old_price',
        'new_price',
        'updated_by',
        'type',
    ];

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
