<?php

namespace App\Models;

use Cache;
use Carbon\Carbon;
use DB;

/**
 * App\Models\ApproveProcInstHistory
 *
 * @property int $id
 * @property int $proc_def_id 流程定义ID
 * @property string|null $proc_def_name 流程定义名
 * @property string|null $title 标题
 * @property int|null $department_id 用户部门ID
 * @property string|null $department 用户部门
 * @property string|null $company 用户公司
 * @property string|null $node_id 当前节点
 * @property string|null $candidate 审批人
 * @property int|null $task_id 当前任务
 * @property string|null $start_time 开始时间
 * @property string|null $end_time 结束时间
 * @property int|null $duration 持续时间
 * @property string|null $start_user_id 开始用户ID
 * @property string|null $start_user_name 开始用户名
 * @property int|null $is_finished 是否完成
 * @property string|null $var
 * @property int $state 当前状态: 0待审批，1审批中，2通过，3拒绝，4撤回
 * @property string|null $latest_comment
 * @property string|null $global_comment
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelAppend()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel cancelHidden()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel change($array)
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel getKeyValue()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel remove()
 * @method static \Illuminate\Database\Eloquent\Builder|AbstractModel saveOrIgnore()
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereCandidate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereCompany($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereGlobalComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereIsFinished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereLatestComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereNodeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereProcDefId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereProcDefName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereStartTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereStartUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereStartUserName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ApproveProcInstHistory whereVar($value)
 * @mixin \Eloquent
 */
class ApproveProcInstHistory extends AbstractModel
{
    protected $table = 'approve_proc_inst_history';

    /**
     * 获取用户审批状态（请假、外出）
     * @param $userid
     * @return mixed|null
     */
    public static function getUserApprovalStatus($userid)
    {
        if (empty($userid)) {
            return null;
        }
        return Cache::remember('user_is_leave_' . $userid, Carbon::now()->addMinute(), function () use ($userid) {
            return self::where([
                ['start_user_id', '=', $userid],
                [DB::raw("JSON_UNQUOTE(JSON_EXTRACT(var, '$.startTime'))"), '<=', Carbon::now()->toDateTimeString()],
                [DB::raw("JSON_UNQUOTE(JSON_EXTRACT(var, '$.endTime'))"), '>=', Carbon::now()->toDateTimeString()],
                ['state', '=', 2]
            ])->where(function ($query) {
                $query->where('proc_def_name', 'like', '%请假%')
                    ->orWhere('proc_def_name', 'like', '%外出%');
            })->orderByDesc('id')->value('proc_def_name');
        });
    }

    /**
     * 判断用户是否请假（包含：请假、外出）
     * @param $userid
     * @return bool
     */
    public static function userIsLeave($userid)
    {
        return (bool)self::getUserApprovalStatus($userid);
    }
}
