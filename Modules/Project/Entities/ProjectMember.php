<?php

namespace Modules\Project\Entities;

use App\User;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB as FacadesDB;

class ProjectMember extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pjt_project_members';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public static function projectMembersDropdown($project_id, $user_id = null)
{
    // Retrieve user IDs associated with the project
    $user_ids = ProjectMember::where('project_id', $project_id)
                    ->pluck('user_id');

    // Retrieve project members with their full names
    $project_members = User::whereIn('id', $user_ids)
                ->select('id', DB::raw("CONCAT(COALESCE(surname, ''),' ',COALESCE(first_name, ''),' ',COALESCE(last_name,'')) as full_name"));

    // Filter by assigned member if provided
    if (!empty($user_id)) {
        $project_members->where('id', $user_id);
    }

    // Pluck the user IDs and full names as a key-value pair
    $project_members = $project_members->pluck('full_name', 'id');

    // Add task count to each project member's name, excluding completed tasks
    $project_members = $project_members->map(function ($name, $id) use ($project_id) {
        $task_count = ProjectTask::where('project_id', $project_id)
            ->whereHas('members', function ($query) use ($id) {
                $query->where('user_id', $id);
            })
            ->whereNotIn('status', ['completed', 'archive']) // Exclude completed and archived tasks
        ->count();
        return "$name ($task_count tasks)";
    });

    return $project_members;
}

}
