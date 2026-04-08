<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 镜头模型
 */
class Shot extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'episode',
        'scene',
        'shot_code',
        'full_path',
        'description',
        'frame_start',
        'frame_end',
        'frame_duration',
        'fps',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'frame_start' => 'integer',
        'frame_end' => 'integer',
        'frame_duration' => 'integer',
        'fps' => 'decimal:2',
    ];

    /**
     * 所属项目
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 创建者
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'userid');
    }

    /**
     * 镜头版本
     */
    public function versions()
    {
        return $this->hasMany(ShotVersion::class);
    }

    /**
     * 反馈
     */
    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'target');
    }

    /**
     * 参考素材
     */
    public function references()
    {
        return $this->hasMany(Reference::class, 'target_id')
            ->where('scope', 'shot');
    }

    /**
     * 获取完整镜头名称
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([
            $this->episode,
            $this->scene,
            $this->shot_code,
        ]);

        return implode('_', $parts);
    }

    /**
     * 获取指定流程的最新版本
     *
     * @param string $stepCode
     * @return ShotVersion|null
     */
    public function getLatestVersion(string $stepCode): ?ShotVersion
    {
        return $this->versions()
            ->where('step_code', $stepCode)
            ->orderBy('version', 'desc')
            ->first();
    }

    /**
     * 计算帧数
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($shot) {
            if ($shot->frame_start && $shot->frame_end) {
                $shot->frame_duration = $shot->frame_end - $shot->frame_start + 1;
            }
        });
    }
}
