<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 参考素材模型
 */
class Reference extends Model
{
    protected $fillable = [
        'scope',
        'target_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'description',
        'tags',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * 创建者
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'userid');
    }

    /**
     * 获取目标对象（根据 scope）
     */
    public function getTargetAttribute()
    {
        switch ($this->scope) {
            case 'project':
                return Project::find($this->target_id);
            case 'asset':
                return Asset::find($this->target_id);
            case 'shot':
                return Shot::find($this->target_id);
            default:
                return null;
        }
    }
}
