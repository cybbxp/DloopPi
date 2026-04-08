<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 流程定义模型
 *
 * 定义项目的制作流程：模型、贴图、绑定、动画等
 */
class PipelineStep extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'code',
        'type',
        'sort',
        'folder_structure',
        'color',
        'is_active',
    ];

    protected $casts = [
        'folder_structure' => 'array',
        'is_active' => 'boolean',
        'sort' => 'integer',
    ];

    /**
     * 所属项目
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * 资产版本
     */
    public function assetVersions()
    {
        return $this->hasMany(AssetVersion::class, 'step_code', 'code');
    }

    /**
     * 镜头版本
     */
    public function shotVersions()
    {
        return $this->hasMany(ShotVersion::class, 'step_code', 'code');
    }

    /**
     * 获取子文件夹列表
     *
     * @return array
     */
    public function getSubfolders(): array
    {
        return $this->folder_structure ?? ['history', 'preview', 'feedback'];
    }
}
