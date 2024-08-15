<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'requisition_no',
        'requisition_by',
        'updated_by',
        'location_id',
        'notes'
    ];

    public function requisitionBy()
    {
        return $this->belongsTo(User::class, 'requisition_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }
}