<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 版本依赖关系模型
 *
 * 用于追踪版本之间的依赖关系
 * 例如：VFX v001 依赖 Animation v010
 */
class VersionDependency extends Model
{
    protected $fillable = [
        'source_type',
        'source_id',
        'target_type',
        'target_id',
        'dependency_type',
    ];

    /**
     * 源版本（多态关联）
     */
    public function source()
    {
        return $this->morphTo();
    }

    /**
     * 目标版本（多态关联）
     */
    public function target()
    {
        return $this->morphTo();
    }
}
