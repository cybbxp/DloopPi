<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id')->comment('项目ID');
            $table->unsignedBigInteger('category_id')->comment('分类ID');
            $table->string('name', 100)->comment('资产名称');
            $table->string('code', 100)->comment('资产编码');
            $table->text('description')->nullable()->comment('资产描述');
            $table->enum('status', ['draft', 'review', 'approved', 'archived'])->default('draft')->comment('状态');
            $table->string('storage_path', 500)->nullable()->comment('存储相对路径');
            $table->string('thumbnail_path', 500)->nullable()->comment('缩略图路径');
            $table->string('preview_path', 500)->nullable()->comment('预览文件路径');
            $table->json('tags')->nullable()->comment('标签');
            $table->json('metadata')->nullable()->comment('自定义元数据');
            $table->integer('latest_version')->default(0)->comment('最新版本号');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamps();

            $table->unique(['project_id', 'code'], 'uk_project_code');
            $table->index('category_id');
            $table->index('status');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
}
