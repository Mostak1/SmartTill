<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinalizeReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_no',
        'date',
        'location_id',
        'date_range_covered',
        'number_of_checks_covered',
        'net_result',
        'finalized_by',
        'comments',
    ];
}
