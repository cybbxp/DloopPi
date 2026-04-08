<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $table = 'assets';

    protected $fillable = [
        'project_id',
        'category_id',
        'name',
        'code',
        'path_name',
        'full_path',
        'description',
        'status',
        'storage_path',
        'thumbnail_path',
        'preview_path',
        'tags',
        'metadata',
        'latest_version',
        'created_by',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联项目
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * 关联分类
     */
    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    /**
     * 关联版本
     */
    public function versions()
    {
        return $this->hasMany(AssetVersion::class, 'asset_id')->orderBy('version', 'desc');
    }

    /**
     * 最新版本
     */
    public function latestVersionModel()
    {
        return $this->hasOne(AssetVersion::class, 'asset_id')->orderBy('version', 'desc');
    }

    /**
     * 关联任务
     */
    public function tasks()
    {
        return $this->hasMany(AssetTask::class, 'asset_id');
    }

    /**
     * 创建人
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'userid');
    }

    /**
     * 获取完整存储路径
     */
    public function getFullStoragePathAttribute()
    {
        return storage_path('assets/' . $this->storage_path);
    }

    /**
     * 获取缩略图 URL
     */
    public function getThumbnailUrlAttribute()
    {
        if (!$this->thumbnail_path) {
            return null;
        }
        return asset('storage/assets/' . $this->thumbnail_path);
    }
}
