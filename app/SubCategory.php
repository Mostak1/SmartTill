<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class SubCategory extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = ['id'];
    protected $fillable = [
        'name', 'category_id', 'business_id', 'short_code', 'parent_id', 'created_by',
        ];
    public function category(){
        return $this->belongsTo(Category::class);
    }
    public function business(){
        return $this->belongsTo(Business::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function products(){
        return $this->hasMany(Product::class);
    }
    public static function forDropdown($business_id)
    {
        $categories = SubCategory::where('business_id', $business_id)
                            ->where('parent_id', 0)
                            ->select(DB::raw('IF(short_code IS NOT NULL, CONCAT(name, "-", short_code), name) as name'), 'id')
                            ->orderBy('name', 'asc')
                            ->get();

        $dropdown = $categories->pluck('name', 'id');

        return $dropdown;
    }
}
