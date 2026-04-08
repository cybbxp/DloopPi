<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFinalMilestoneFieldsToProjectTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->string('final_milestone_name', 100)->nullable()->default('Final')->comment('Final 里程碑名称')->after('color');
            $table->string('final_milestone_color', 20)->nullable()->default('#52c41a')->comment('Final 里程碑颜色')->after('final_milestone_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn(['final_milestone_name', 'final_milestone_color']);
        });
    }
}
