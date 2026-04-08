<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAssetsUseExistingProjects extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 删除 asset_projects 表
        Schema::dropIfExists('asset_projects');

        // 修改 assets 表，让 project_id 关联到 projects 表
        Schema::table('assets', function (Blueprint $table) {
            // 添加外键关联到 projects 表
            $table->foreign('project_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 恢复 asset_projects 表
        Schema::create('asset_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('项目名称');
            $table->string('code', 50)->unique()->comment('项目代码');
            $table->text('description')->nullable()->comment('项目描述');
            $table->enum('status', ['active', 'archived'])->default('active')->comment('状态');
            $table->string('storage_path')->comment('存储路径');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
        });

        // 恢复 assets 表的外键
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['project_id']);

            $table->foreign('project_id')
                ->references('id')
                ->on('asset_projects')
                ->onDelete('cascade');
        });
    }
}
