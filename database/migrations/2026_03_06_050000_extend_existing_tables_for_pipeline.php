<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 扩展现有表以支持 CG Pipeline
 */
class ExtendExistingTablesForPipeline extends Migration
{
    public function up()
    {
        // 1. 扩展 projects 表
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'storage_root')) {
                $table->string('storage_root', 500)->nullable()->comment('存储根路径，如 H:\ProjectA');
            }
            if (!Schema::hasColumn('projects', 'storage_type')) {
                $table->enum('storage_type', ['local', 'network', 'cloud'])->default('local')->comment('存储类型');
            }
            if (!Schema::hasColumn('projects', 'project_code')) {
                $table->string('project_code', 50)->nullable()->comment('项目代码，用于文件命名');
            }
            if (!Schema::hasColumn('projects', 'asset_template_id')) {
                $table->bigInteger('asset_template_id')->nullable()->comment('资产路径模板ID');
            }
            if (!Schema::hasColumn('projects', 'shot_template_id')) {
                $table->bigInteger('shot_template_id')->nullable()->comment('镜头路径模板ID');
            }
            if (!Schema::hasColumn('projects', 'naming_rules')) {
                $table->json('naming_rules')->nullable()->comment('命名规则配置');
            }
        });

        // 2. 扩展 assets 表
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'path_name')) {
                $table->string('path_name', 100)->nullable()->comment('用于路径的资产名称（无特殊字符）');
            }
            if (!Schema::hasColumn('assets', 'full_path')) {
                $table->string('full_path', 500)->nullable()->comment('完整相对路径：Assets/characters/HeroA');
            }
        });

        // 3. 扩展 asset_versions 表
        Schema::table('asset_versions', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_versions', 'step_code')) {
                $table->string('step_code', 50)->nullable()->comment('流程代码：model, texture, rig');
            }
            if (!Schema::hasColumn('asset_versions', 'is_current')) {
                $table->boolean('is_current')->default(false)->comment('是否当前版本');
            }
            if (!Schema::hasColumn('asset_versions', 'file_path_current')) {
                $table->string('file_path_current', 500)->nullable()->comment('当前文件路径：HeroA_Rig.ma');
            }
            if (!Schema::hasColumn('asset_versions', 'file_path_history')) {
                $table->string('file_path_history', 500)->nullable()->comment('历史文件路径：history/HeroA_Rig_v003.ma');
            }
            if (!Schema::hasColumn('asset_versions', 'preview_path')) {
                $table->string('preview_path', 500)->nullable()->comment('预览图路径');
            }
        });
    }

    public function down()
    {
        Schema::table('asset_versions', function (Blueprint $table) {
            $table->dropColumn(['step_code', 'is_current', 'file_path_current', 'file_path_history', 'preview_path']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['path_name', 'full_path']);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['storage_root', 'storage_type', 'project_code', 'asset_template_id', 'shot_template_id', 'naming_rules']);
        });
    }
}
