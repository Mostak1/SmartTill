<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportItem extends Model
{
    use HasFactory;

    protected $table = 'report_items';

    protected $fillable = [
        'report_id',
        'type',
        'category_name',
        'product_name',
        'sku',
        'brand_name',
        'quantity',
        'subtotal',
        'comment',
    ];

    public function report()
    {
        return $this->belongsTo(FinalizeReport::class, 'report_id');
    }
}
