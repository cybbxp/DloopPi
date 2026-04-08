<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 镜头版本模型
 */
class ShotVersion extends Model
{
    protected $fillable = [
        'shot_id',
        'step_code',
        'version',
        'file_path',
        'preview_path',
        'depends_on_version_id',
        'comment',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'version' => 'integer',
    ];

    /**
     * 所属镜头
     */
    public function shot()
    {
        return $this->belongsTo(Shot::class);
    }

    /**
     * 创建者
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'userid');
    }

    /**
     * 依赖的上游版本
     */
    public function dependsOnVersion()
    {
        return $this->belongsTo(ShotVersion::class, 'depends_on_version_id');
    }

    /**
     * 被依赖的下游版本
     */
    public function dependentVersions()
    {
        return $this->hasMany(ShotVersion::class, 'depends_on_version_id');
    }

    /**
     * 反馈
     */
    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'target');
    }

    /**
     * 版本依赖关系（作为源）
     */
    public function dependenciesAsSource()
    {
        return $this->morphMany(VersionDependency::class, 'source');
    }

    /**
     * 版本依赖关系（作为目标）
     */
    public function dependenciesAsTarget()
    {
        return $this->morphMany(VersionDependency::class, 'target');
    }

    /**
     * 获取格式化版本号
     *
     * @return string
     */
    public function getFormattedVersionAttribute(): string
    {
        return sprintf('v%03d', $this->version);
    }
}
