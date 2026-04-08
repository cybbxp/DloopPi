<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * 默认流程步骤预设
 *
 * 提供影视制作的标准流程
 */
class PipelineStepSeeder extends Seeder
{
    /**
     * 获取资产流程预设
     */
    public static function getAssetSteps(): array
    {
        return [
            [
                'name' => '模型',
                'code' => 'model',
                'type' => 'asset',
                'sort' => 1,
                'folder_structure' => ['history', 'preview', 'feedback', 'zbrush', 'maya'],
                'color' => '#3498db',
            ],
            [
                'name' => '贴图',
                'code' => 'texture',
                'type' => 'asset',
                'sort' => 2,
                'folder_structure' => ['history', 'preview', 'feedback', 'substance', 'maps'],
                'color' => '#e74c3c',
            ],
            [
                'name' => '绑定',
                'code' => 'rig',
                'type' => 'asset',
                'sort' => 3,
                'folder_structure' => ['history', 'preview', 'feedback'],
                'color' => '#9b59b6',
            ],
            [
                'name' => '动画',
                'code' => 'animation',
                'type' => 'asset',
                'sort' => 4,
                'folder_structure' => ['history', 'preview', 'feedback'],
                'color' => '#f39c12',
            ],
        ];
    }

    /**
     * 获取镜头流程预设
     */
    public static function getShotSteps(): array
    {
        return [
            [
                'name' => 'Layout',
                'code' => 'layout',
                'type' => 'shot',
                'sort' => 1,
                'folder_structure' => ['history', 'preview', 'feedback'],
                'color' => '#1abc9c',
            ],
            [
                'name' => 'Animation',
                'code' => 'anim',
                'type' => 'shot',
                'sort' => 2,
                'folder_structure' => ['history', 'preview', 'feedback', 'abc'],
                'color' => '#f39c12',
            ],
            [
                'name' => 'CFX',
                'code' => 'cfx',
                'type' => 'shot',
                'sort' => 3,
                'folder_structure' => ['history', 'preview', 'feedback', 'abc', 'cache'],
                'color' => '#3498db',
            ],
            [
                'name' => 'VFX',
                'code' => 'vfx',
                'type' => 'shot',
                'sort' => 4,
                'folder_structure' => ['history', 'preview', 'feedback', 'abc', 'cache', 'houdini'],
                'color' => '#e67e22',
            ],
            [
                'name' => 'Light',
                'code' => 'light',
                'type' => 'shot',
                'sort' => 5,
                'folder_structure' => ['history', 'preview', 'feedback', 'render'],
                'color' => '#f1c40f',
            ],
            [
                'name' => 'Comp',
                'code' => 'comp',
                'type' => 'shot',
                'sort' => 6,
                'folder_structure' => ['history', 'preview', 'feedback', 'nuke', 'output'],
                'color' => '#e74c3c',
            ],
        ];
    }

    /**
     * 为指定项目创建默认流程
     */
    public static function createForProject(int $projectId, array $steps = null)
    {
        if ($steps === null) {
            $steps = array_merge(
                self::getAssetSteps(),
                self::getShotSteps()
            );
        }

        foreach ($steps as $step) {
            $step['project_id'] = $projectId;
            $step['is_active'] = true;
            $step['created_at'] = now();
            $step['updated_at'] = now();
            $step['folder_structure'] = json_encode($step['folder_structure']);

            \DB::table('pipeline_steps')->insert($step);
        }
    }

    public function run()
    {
        // 这个 Seeder 主要用于提供静态方法
        // 实际创建流程时，在项目创建时调用 createForProject()
    }
}
