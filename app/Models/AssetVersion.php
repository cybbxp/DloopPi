<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetVersion extends Model
{
    protected $table = 'asset_versions';

    public $timestamps = false;

    protected $fillable = [
        'asset_id',
        'step_code',
        'version',
        'is_current',
        'file_path',
        'file_path_current',
        'file_path_history',
        'file_name',
        'file_size',
        'file_hash',
        'mime_type',
        'extension',
        'thumbnail_path',
        'preview_path',
        'comment',
        'status',
        'task_id',
        'created_by',
        'created_at',
    ];

    protected $casts = [
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
     * 创建人
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'userid');
    }

    /**
     * 获取完整文件路径
     */
    public function getFullFilePathAttribute()
    {
        return storage_path('assets/' . $this->file_path);
    }

    /**
     * 获取文件下载 URL
     */
    public function getDownloadUrlAttribute()
    {
        return route('api.assets.versions.download', [
            'asset' => $this->asset_id,
            'version' => $this->version,
        ]);
    }

    /**
     * 格式化文件大小
     */
    public function getFileSizeFormattedAttribute()
    {
        if (!$this->file_size) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->file_size;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
