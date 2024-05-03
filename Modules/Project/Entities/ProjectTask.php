<?php

namespace Modules\Project\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProjectTask extends Model
{
    use LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pjt_project_tasks';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected static $logUnguarded = true;

    protected static $logOnlyDirty = true;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    /**
     * The member that belong to the task.
     */
    public function members()
    {
        return $this->belongsToMany(\App\User::class, 'pjt_project_task_members', 'project_task_id', 'user_id');
    }

    /**
     * Return the creator of task.
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Return the project for a task.
     */
    public function project()
    {
        return $this->belongsTo('Modules\Project\Entities\Project', 'project_id');
    }

    /**
     * Get the comments for the task.
     */
    public function comments()
    {
        return $this->hasMany('Modules\Project\Entities\ProjectTaskComment');
    }

    /**
     * Get the time logs for the task.
     */
    public function timeLogs()
    {
        return $this->hasMany('Modules\Project\Entities\ProjectTimeLog');
    }

    /**
     * Return the priority for project task.
     */
    public static function prioritiesDropdown()
    {
        $priorities = [
            'low' => __('project::lang.low'),
            'medium' => __('project::lang.medium'),
            'high' => __('project::lang.high'),
            'urgent' => __('project::lang.urgent'),
        ];

        return $priorities;
    }

    /**
     * Return the colors for priority
     */
    public static function priorityColors()
    {
        $priority_colors = [
            'low' => 'bg-green',
            'medium' => 'bg-yellow',
            'high' => 'bg-orange',
            'urgent' => 'bg-red',
        ];

        return $priority_colors;
    }

    /**
     * Return the task for dropdown.
     */
    public static function taskDropdown($project_id)
    {
        $project_tasks = ProjectTask::where('project_id', $project_id)
            ->select('id', DB::raw("concat(subject, ' (', task_id, ')') as subject"))
            ->pluck('subject', 'id');

        return $project_tasks;
    }

    /**
     * Return the status for task.
     */
    public static function taskStatuses($project_id)
    {
        if ($project_id) {
            $project = Project::where('id', $project_id)->first();
        } else {
            $statuses = [
                'not_started' => __('project::lang.not_started'),
                'in_progress' => __('project::lang.in_progress'),
                'on_hold' => __('project::lang.on_hold'),
                'cancelled' => __('project::lang.cancelled'),
                'completed' => __('project::lang.completed'),
            ];
            return $statuses;
        }
        
        $settings = $project->settings;
        $notStart = $project->settings['not_started'] ?? false;
        $in_progress = $project->settings['in_progress'] ?? false;
        $on_hold = $project->settings['on_hold'] ?? false;
        $cancelled = $project->settings['cancelled'] ?? false;
        $completed = $project->settings['completed'] ?? false;
        $statuses = [
            'not_started' => isset($notStart['name']) ? $notStart['name'] : __('project::lang.not_started'),
            'in_progress' => isset($in_progress['name']) ? $in_progress['name'] : __('project::lang.in_progress'),
            'on_hold' => isset($on_hold['name']) ? $on_hold['name'] : __('project::lang.on_hold'),
            'cancelled' => isset($cancelled['name']) ? $cancelled['name'] : __('project::lang.cancelled'),
            'completed' => isset($completed['name']) ? $completed['name'] : __('project::lang.completed'),
        ];
        // Filtered status
        $filtered_status = [];
        if (isset($settings['not_started']['id']) && $settings['not_started']['id'] == 1) {
            $filtered_status['not_started'] = $statuses['not_started'];
        }
        if (isset($settings['in_progress']['id']) && $settings['in_progress']['id'] == 1) {
            $filtered_status['in_progress'] = $statuses['in_progress'];
        }
        if (isset($settings['on_hold']['id']) && $settings['on_hold']['id'] == 1) {
            $filtered_status['on_hold'] = $statuses['on_hold'];
        }
        if (isset($settings['cancelled']['id']) && $settings['cancelled']['id'] == 1) {
            $filtered_status['cancelled'] = $statuses['cancelled'];
        }
        if (isset($settings['completed']['id']) && $settings['completed']['id'] == 1) {
            $filtered_status['completed'] = $statuses['completed'];
        }
        // return $filtered_status;
        if ($filtered_status) {
            return $filtered_status;
        } else {
            return $statuses;
        }
    }
    public static function statusesId($project_id)
    {
        if ($project_id) {
            $project = Project::where('id', $project_id)->first();
        } else {
            $statuses = [
                'not_started' => __('project::lang.not_started'),
                'in_progress' => __('project::lang.in_progress'),
                'on_hold' => __('project::lang.on_hold'),
                'cancelled' => __('project::lang.cancelled'),
                'completed' => __('project::lang.completed'),
            ];
            return $statuses;
        }
         $settings = $project->settings;
        $statuses = [
            'not_started' => $settings['not_started']['id'],
            'in_progress' => $settings['in_progress']['id'],
            'on_hold' => $settings['on_hold']['id'],
            'cancelled' => $settings['cancelled']['id'],
            'completed' => $settings['completed']['id'],
        ];

        return $statuses;
    }
    public static function statusesColor($project_id)
    {
        $project = Project::where('id', $project_id)->first();
        $settings = $project->settings['not_started'];
        $statuses = [
            'not_started' => $settings['name'],
            'in_progress' => __('project::lang.in_progress'),
            'on_hold' => __('project::lang.on_hold'),
            'cancelled' => __('project::lang.cancelled'),
            'completed' => __('project::lang.completed'),
        ];

        return $statuses;
    }
    public static function taskStatuses1()
    {
        $statuses = [
            'not_started' => __('project::lang.not_started'),
            'in_progress' => __('project::lang.in_progress'),
            'on_hold' => __('project::lang.on_hold'),
            'cancelled' => __('project::lang.cancelled'),
            'completed' => __('project::lang.completed'),
        ];

        return $statuses;
    }

    /**
     * Return the due dates for task.
     */
    public static function dueDatesDropdown()
    {
        $due_dates = [
            'overdue' => __('project::lang.overdue'),
            'today' => __('home.today'),
            'less_than_one_week' => __('project::lang.less_than_1_week'),
        ];

        return $due_dates;
    }
}
