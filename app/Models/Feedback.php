<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 反馈模型
 */
class Feedback extends Model
{
    protected $fillable = [
        'target_type',
        'target_id',
        'feedback_folder',
        'files',
        'content',
        'status',
        'created_by',
        'assigned_to',
    ];

    protected $casts = [
        'files' => 'array',
    ];

    /**
     * 反馈目标（多态关联）
     */
    public function target()
    {
        return $this->morphTo();
    }

    /**
     * 创建者
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'userid');
    }

    /**
     * 分配给
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'userid');
    }
}
