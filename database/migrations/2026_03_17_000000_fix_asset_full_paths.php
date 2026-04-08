<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Asset;
use App\Models\AssetCategory;

class FixAssetFullPaths extends Migration
{
    public function up()
    {
        // 获取所有 full_path 为空的资产
        $assets = Asset::whereNull('full_path')
            ->orWhere('full_path', '')
            ->with('category')
            ->get();

        foreach ($assets as $asset) {
            if (!$asset->category) {
                continue;
            }

            // 生成 path_name（用于路径的资产名称）
            $pathName = $this->sanitizePathName($asset->name);

            // 生成相对路径：Assets/{category_code}/{asset_path_name}
            $fullPath = "Assets/{$asset->category->code}/{$pathName}";

            // 更新资产
            $asset->update([
                'path_name' => $pathName,
                'full_path' => $fullPath,
            ]);
        }
    }

    public function down()
    {
        // 不需要回滚
    }

    /**
     * 清理路径名称（去除特殊字符）
     */
    private function sanitizePathName(string $name): string
    {
        // 保留中文、英文、数字、下划线、连字符
        $name = preg_replace('/[^\p{L}\p{N}_-]/u', '_', $name);
        return trim($name, '_');
    }
}
