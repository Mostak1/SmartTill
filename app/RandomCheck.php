<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RandomCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'checked_by',
        'modified_by',
        'check_no',
        'comment',
    ];

    // Define the relationship with the RandomCheckDetails model
    public function randomCheckDetails()
    {
        return $this->hasMany(RandomCheckDetail::class, 'random_check_id');
    }

    // Define the relationship with the User model for the user who checked
    public function checkedBy()
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    // Define the relationship with the User model for the user who modified
    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
