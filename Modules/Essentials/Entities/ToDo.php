<?php

namespace Modules\Essentials\Entities;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ToDo extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    use HasFactory, SoftDeletes;
    protected $guarded = ['id'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'essentials_to_dos';

    public function users()
    {
        return $this->belongsToMany(\App\User::class, 'essentials_todos_users', 'todo_id', 'user_id');
    }

    public function assigned_by()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(\Modules\Essentials\Entities\EssentialsTodoComment::class, 'task_id')->orderBy('id', 'desc');
    }

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    public static function getTaskStatus()
    {
        $statuses = [
            'new' => __('essentials::lang.new'),
            'in_progress' => __('essentials::lang.in_progress'),
            'on_hold' => __('essentials::lang.on_hold'),
            'completed' => __('restaurant.completed'),
        ];

        return $statuses;
    }

    public static function getTaskPriorities()
    {
        $priorities = [
            'low' => __('essentials::lang.low'),
            'medium' => __('essentials::lang.medium'),
            'high' => __('essentials::lang.high'),
            'urgent' => __('essentials::lang.urgent'),
        ];

        return $priorities;
    }

    /**
     * Attributes to be logged for activity
     */
    public function getLogPropertiesAttribute()
    {
        $properties = ['status'];

        return $properties;
    }

    public static function userTodoDropdown($business_id, $prepend_none = true, $include_commission_agents = false, $prepend_all = false, $check_location_permission = false)
    {
        $query = User::with('todos')->where('business_id', $business_id)->get();
        $users = [];
        foreach ($query as $q) {
            $total = 0;
            foreach ($q->todos as $todo) {
                if ($todo->created_by == Auth::user()->id) {
                    $total++;
                }
            }
            $totatTodo = $q->todos->count();
            if ((auth()->user()->can('superadmin'))) {
                if ($totatTodo > 0) {
                    $users[$q->id] = $q->first_name . ' ' . $q->last_name . " ($totatTodo)";
                }
            } else {
                if ($total > 0) {
                    $users[$q->id] = $q->first_name . ' ' . $q->last_name . " ($total)";
                }
            }
        }
        return $users;
    }
    public static function userTodoDropdownAssignedBy($business_id, $prepend_none = true, $include_commission_agents = false, $prepend_all = false, $check_location_permission = false)
    {
        $query = User::with('todos')->where('business_id', $business_id)->get();
        $users = [];
        foreach ($query as $q) {
            $total = 0;
            foreach ($q->todos as $todo) {
                if ($todo->created_by == Auth::user()->id) {
                    $total++;
                }
            }
            if ($total > 0) {
                $users[$q->id] = $q->first_name . ' ' . $q->last_name . " ($total)";
            }
        }
        return $users;
    }
}
