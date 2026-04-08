<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Project;
use App\Module\AssetLibrary;

class FixAssetPaths extends Command
{
    protected $signature = 'assets:fix-paths';
    protected $description = '修复资产路径格式';

    public function handle()
    {
        $assetLibrary = new AssetLibrary();

        $assets = Asset::with(['project', 'category'])->get();

        $this->info("找到 {$assets->count()} 个资产");

        foreach ($assets as $asset) {
            if (!$asset->category) {
                $this->warn("资产 {$asset->id} 没有分类，跳过");
                continue;
            }

            // 生成 path_name
            $pathName = preg_replace('/[^\p{L}\p{N}_-]/u', '_', $asset->name);
            $pathName = trim($pathName, '_');

            // 生成相对路径
            $categoryCode = $asset->category->code ?? 'unknown';
            $relativePath = "Assets/{$categoryCode}/{$pathName}";

            // 更新数据库
            $asset->path_name = $pathName;
            $asset->full_path = $relativePath;
            $asset->save();

            $this->info("更新资产 {$asset->id}: {$asset->name} -> {$relativePath}");
            $this->info("  path_name: {$asset->path_name}");
            $this->info("  full_path: {$asset->full_path}");
        }

        $this->info("完成！");

        return 0;
    }
}
