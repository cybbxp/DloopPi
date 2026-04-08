<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $table = 'asset_categories';

    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'path_template',
        'sort',
    ];

    protected $casts = [
        'path_template' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 关联资产
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_id');
    }

    /**
     * 父分类
     */
    public function parent()
    {
        return $this->belongsTo(AssetCategory::class, 'parent_id');
    }

    /**
     * 子分类
     */
    public function children()
    {
        return $this->hasMany(AssetCategory::class, 'parent_id');
    }
}
