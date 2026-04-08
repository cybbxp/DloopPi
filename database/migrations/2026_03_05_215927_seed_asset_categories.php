<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedAssetCategories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $categories = [
            [
                'name' => '角色',
                'code' => 'character',
                'type' => 'character',
                'path_template' => json_encode(['model', 'texture', 'rig', 'animation']),
                'sort' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '道具',
                'code' => 'prop',
                'type' => 'prop',
                'path_template' => json_encode(['model', 'texture']),
                'sort' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '场景',
                'code' => 'environment',
                'type' => 'environment',
                'path_template' => json_encode(['model', 'texture', 'lighting']),
                'sort' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '载具',
                'code' => 'vehicle',
                'type' => 'vehicle',
                'path_template' => json_encode(['model', 'texture', 'rig']),
                'sort' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '其他',
                'code' => 'other',
                'type' => 'other',
                'path_template' => json_encode(['files']),
                'sort' => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('asset_categories')->insert($categories);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('asset_categories')->whereIn('code', ['character', 'prop', 'environment', 'vehicle', 'other'])->delete();
    }
}

