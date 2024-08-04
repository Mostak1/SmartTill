<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RandomCheckDetail extends Model
{
    use HasFactory,SoftDeletes;

    protected $dates =['deleted_at'];
    protected $fillable = [
        'random_check_id',
        'product_id',
        'location_id',
        'variation_id',
        'category_name',
        'product_name',
        'sku',
        'brand_name',
        'current_stock',
        'physical_count',
        'comment',
    ];

    // Define the relationship with the RandomCheck model
    public function randomCheck()
    {
        return $this->belongsTo(RandomCheck::class);
    }

    // Define the relationship with the Product model
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Define the relationship with the BusinessLocation model
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    // Define the relationship with the Variation model
    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
}
