<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTask extends Model
{
    protected $table = 'asset_tasks';

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'task_id',
        'role',
        'assigned_to',
        'status',
        'start_at',
        'deadline_at',
        'completed_at',
        'created_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'deadline_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * 关联资产
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    /**
     * 关联任务
     */
    public function task()
    {
        return $this->belongsTo(\App\Models\ProjectTask::class, 'task_id', 'id');
    }

    /**
     * 分配给谁
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'userid');
    }
}
