<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->comment('资产ID');
            $table->unsignedBigInteger('task_id')->comment('DooTask任务ID');
            $table->enum('role', ['modeling', 'texturing', 'rigging', 'animation', 'lighting', 'compositing', 'other'])->comment('制作角色');
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('分配给谁');
            $table->enum('status', ['pending', 'in_progress', 'review', 'completed', 'rejected'])->default('pending')->comment('状态');
            $table->timestamp('start_at')->nullable()->comment('开始时间');
            $table->timestamp('deadline_at')->nullable()->comment('截止时间');
            $table->timestamp('completed_at')->nullable()->comment('完成时间');
            $table->timestamp('created_at')->nullable();

            $table->unique(['asset_id', 'task_id'], 'uk_asset_task');
            $table->index('assigned_to');
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
        Schema::dropIfExists('asset_tasks');
    }
}
