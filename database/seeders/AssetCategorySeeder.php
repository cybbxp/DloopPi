<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetCategory;

class AssetCategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => '角色', 'code' => 'character', 'type' => 'character', 'sort' => 1],
            ['name' => '道具', 'code' => 'prop', 'type' => 'prop', 'sort' => 2],
            ['name' => '场景', 'code' => 'environment', 'type' => 'environment', 'sort' => 3],
            ['name' => '载具', 'code' => 'vehicle', 'type' => 'vehicle', 'sort' => 4],
            ['name' => '其他', 'code' => 'other', 'type' => 'other', 'sort' => 5],
        ];

        foreach ($categories as $cat) {
            AssetCategory::firstOrCreate(
                ['code' => $cat['code']],
                $cat
            );
        }

        $this->command->info('资产分类创建完成: ' . AssetCategory::count() . ' 个');
    }
}
