<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('分类名称');
            $table->string('code', 50)->comment('分类代码');
            $table->enum('type', ['character', 'prop', 'environment', 'vehicle', 'other'])->comment('分类类型');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('父分类ID');
            $table->json('path_template')->nullable()->comment('目录模板');
            $table->integer('sort')->default(0)->comment('排序');
            $table->timestamps();

            $table->index('type');
            $table->index('parent_id');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_categories');
    }
}
