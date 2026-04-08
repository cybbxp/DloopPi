<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->comment('资产ID');
            $table->integer('version')->comment('版本号');
            $table->string('file_path', 500)->comment('文件相对路径');
            $table->string('file_name', 255)->nullable()->comment('原始文件名');
            $table->bigInteger('file_size')->nullable()->comment('文件大小（字节）');
            $table->string('file_hash', 64)->nullable()->comment('SHA256哈希');
            $table->string('mime_type', 100)->nullable()->comment('MIME类型');
            $table->string('extension', 20)->nullable()->comment('文件扩展名');
            $table->string('thumbnail_path', 500)->nullable()->comment('缩略图路径');
            $table->string('preview_path', 500)->nullable()->comment('预览文件路径');
            $table->text('comment')->nullable()->comment('版本说明');
            $table->enum('status', ['pending', 'approved', 'rejected', 'archived'])->default('pending')->comment('状态');
            $table->unsignedBigInteger('task_id')->nullable()->comment('关联任务ID');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamp('created_at')->nullable();

            $table->unique(['asset_id', 'version'], 'uk_asset_version');
            $table->index('task_id');
            $table->index('file_hash');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_versions');
    }
}
