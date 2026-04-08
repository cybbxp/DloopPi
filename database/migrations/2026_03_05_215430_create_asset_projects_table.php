<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('项目名称');
            $table->string('code', 50)->unique()->comment('项目代码');
            $table->text('description')->nullable()->comment('项目描述');
            $table->enum('status', ['active', 'archived', 'paused'])->default('active')->comment('状态');
            $table->string('storage_path', 255)->nullable()->comment('存储根路径');
            $table->unsignedBigInteger('created_by')->nullable()->comment('创建人');
            $table->timestamps();

            $table->index('code');
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
        Schema::dropIfExists('asset_projects');
    }
}
