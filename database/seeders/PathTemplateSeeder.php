<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * 路径模板预设数据
 *
 * 提供影视、游戏等行业的标准路径模板
 */
class PathTemplateSeeder extends Seeder
{
    public function run()
    {
        $templates = [
            // 1. 影视资产标准模板
            [
                'name' => '影视资产标准模板',
                'type' => 'asset',
                'structure' => [
                    [
                        'level' => 1,
                        'name' => 'Assets',
                        'fixed' => true,
                    ],
                    [
                        'level' => 2,
                        'name' => 'category',
                        'source' => 'category.code',
                        'fixed' => false,
                    ],
                    [
                        'level' => 3,
                        'name' => 'asset_name',
                        'source' => 'asset.path_name',
                        'fixed' => false,
                    ],
                    [
                        'level' => 4,
                        'name' => 'step',
                        'source' => 'step.code',
                        'fixed' => false,
                    ],
                ],
                'naming_rules' => [
                    'work_file' => '{asset_name}_{step}.{ext}',
                    'history_file' => '{asset_name}_{step}_v{version:03d}.{ext}',
                    'preview' => '{asset_name}_{step}_v{version:03d}.{ext}',
                ],
                'subfolders' => ['history', 'preview', 'feedback'],
                'is_system' => true,
                'description' => '适用于影视动画项目的资产管理，支持角色、道具、场景等分类',
            ],

            // 2. 游戏资产标准模板
            [
                'name' => '游戏资产标准模板',
                'type' => 'asset',
                'structure' => [
                    [
                        'level' => 1,
                        'name' => 'Assets',
                        'fixed' => true,
                    ],
                    [
                        'level' => 2,
                        'name' => 'category',
                        'source' => 'category.code',
                        'fixed' => false,
                    ],
                    [
                        'level' => 3,
                        'name' => 'asset_name',
                        'source' => 'asset.path_name',
                        'fixed' => false,
                    ],
                    [
                        'level' => 4,
                        'name' => 'step',
                        'source' => 'step.code',
                        'fixed' => false,
                    ],
                ],
                'naming_rules' => [
                    'work_file' => '{asset_name}_{step}.{ext}',
                    'history_file' => '{asset_name}_{step}_v{version:03d}.{ext}',
                    'preview' => '{asset_name}_{step}.png',
                ],
                'subfolders' => ['history', 'preview', 'export'],
                'is_system' => true,
                'description' => '适用于游戏项目的资产管理',
            ],

            // 3. 影视剧集镜头模板
            [
                'name' => '影视剧集镜头模板',
                'type' => 'shot',
                'structure' => [
                    [
                        'level' => 1,
                        'name' => 'Shots',
                        'fixed' => true,
                    ],
                    [
                        'level' => 2,
                        'name' => 'step',
                        'source' => 'step.name',
                        'fixed' => false,
                    ],
                    [
                        'level' => 3,
                        'name' => 'episode',
                        'source' => 'shot.episode',
                        'optional' => true,
                    ],
                    [
                        'level' => 4,
                        'name' => 'scene',
                        'source' => 'shot.scene',
                        'optional' => true,
                    ],
                    [
                        'level' => 5,
                        'name' => 'shot_code',
                        'source' => 'shot.shot_code',
                        'fixed' => false,
                    ],
                ],
                'naming_rules' => [
                    'work_file' => '{project_code}_{episode}_{scene}_{shot_code}_{step}_v{version:03d}.{ext}',
                    'preview' => '{project_code}_{episode}_{scene}_{shot_code}_{step}_v{version:03d}.mov',
                ],
                'subfolders' => ['history', 'preview', 'feedback', 'abc', 'cache'],
                'is_system' => true,
                'description' => '适用于电视剧、网剧等剧集项目，支持集数、场次、镜头号层级',
            ],

            // 4. 电影镜头模板（无集数）
            [
                'name' => '电影镜头模板',
                'type' => 'shot',
                'structure' => [
                    [
                        'level' => 1,
                        'name' => 'Shots',
                        'fixed' => true,
                    ],
                    [
                        'level' => 2,
                        'name' => 'step',
                        'source' => 'step.name',
                        'fixed' => false,
                    ],
                    [
                        'level' => 3,
                        'name' => 'scene',
                        'source' => 'shot.scene',
                        'optional' => true,
                    ],
                    [
                        'level' => 4,
                        'name' => 'shot_code',
                        'source' => 'shot.shot_code',
                        'fixed' => false,
                    ],
                ],
                'naming_rules' => [
                    'work_file' => '{project_code}_{scene}_{shot_code}_{step}_v{version:03d}.{ext}',
                    'preview' => '{project_code}_{scene}_{shot_code}_{step}_v{version:03d}.mov',
                ],
                'subfolders' => ['history', 'preview', 'feedback', 'abc', 'cache'],
                'is_system' => true,
                'description' => '适用于电影项目，无集数层级',
            ],

            // 5. 简化资产模板（小型项目）
            [
                'name' => '简化资产模板',
                'type' => 'asset',
                'structure' => [
                    [
                        'level' => 1,
                        'name' => 'Assets',
                        'fixed' => true,
                    ],
                    [
                        'level' => 2,
                        'name' => 'asset_name',
                        'source' => 'asset.path_name',
                        'fixed' => false,
                    ],
                    [
                        'level' => 3,
                        'name' => 'step',
                        'source' => 'step.code',
                        'fixed' => false,
                    ],
                ],
                'naming_rules' => [
                    'work_file' => '{asset_name}_{step}.{ext}',
                    'history_file' => '{asset_name}_{step}_v{version:03d}.{ext}',
                ],
                'subfolders' => ['history', 'preview'],
                'is_system' => true,
                'description' => '简化版资产模板，适合小型项目或快速原型',
            ],
        ];

        foreach ($templates as $template) {
            $template['structure'] = json_encode($template['structure']);
            $template['naming_rules'] = json_encode($template['naming_rules']);
            $template['subfolders'] = json_encode($template['subfolders']);
            $template['created_at'] = now();
            $template['updated_at'] = now();

            DB::table('path_templates')->insert($template);
        }
    }
}
