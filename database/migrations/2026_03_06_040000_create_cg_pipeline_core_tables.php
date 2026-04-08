<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CG Pipeline 核心表
 *
 * 分步创建，避免与现有表冲突
 */
class CreateCgPipelineCoreTables extends Migration
{
    public function up()
    {
        // 1. 路径模板表
        Schema::create('path_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('模板名称');
            $table->enum('type', ['asset', 'shot'])->comment('模板类型：资产/镜头');
            $table->json('structure')->comment('路径结构配置 JSON');
            $table->json('naming_rules')->nullable()->comment('命名规则');
            $table->json('subfolders')->nullable()->comment('子文件夹配置：history, preview, feedback等');
            $table->boolean('is_system')->default(false)->comment('是否系统预设');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_system']);
        });

        // 2. 流程定义表（Pipeline Steps）
        Schema::create('pipeline_steps', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->comment('项目ID');
            $table->string('name', 50)->comment('流程名称：模型、贴图、绑定等');
            $table->string('code', 50)->comment('流程代码：model, texture, rig');
            $table->enum('type', ['asset', 'shot', 'both'])->comment('适用类型');
            $table->integer('sort')->default(0)->comment('排序');
            $table->json('folder_structure')->nullable()->comment('该流程的子文件夹结构');
            $table->string('color', 20)->nullable()->comment('UI 显示颜色');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['project_id', 'type']);
            $table->unique(['project_id', 'code']);
        });

        // 3. 镜头表
        Schema::create('shots', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id')->comment('项目ID');
            $table->string('episode', 50)->nullable()->comment('集数：Ep01');
            $table->string('scene', 50)->nullable()->comment('场次：Sc01');
            $table->string('shot_code', 50)->comment('镜头号：Shot010');
            $table->string('full_path', 500)->nullable()->comment('完整相对路径');
            $table->text('description')->nullable();
            $table->integer('frame_start')->nullable()->comment('起始帧');
            $table->integer('frame_end')->nullable()->comment('结束帧');
            $table->integer('frame_duration')->nullable()->comment('帧数');
            $table->decimal('fps', 5, 2)->default(24.00)->comment('帧率');
            $table->enum('status', ['pending', 'in_progress', 'review', 'approved', 'final'])->default('pending');
            $table->json('metadata')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'episode', 'scene']);
            $table->unique(['project_id', 'episode', 'scene', 'shot_code']);
        });

        // 4. 镜头版本表
        Schema::create('shot_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shot_id')->comment('镜头ID');
            $table->string('step_code', 50)->comment('流程代码：layout, anim, vfx');
            $table->integer('version')->comment('版本号');
            $table->string('file_path', 500)->comment('文件相对路径');
            $table->string('preview_path', 500)->nullable()->comment('预览视频路径');
            $table->bigInteger('depends_on_version_id')->nullable()->comment('依赖的上游版本ID');
            $table->text('comment')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'final'])->default('draft');
            $table->json('metadata')->nullable()->comment('帧范围、分辨率、渲染设置等');
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['shot_id', 'step_code']);
            $table->unique(['shot_id', 'step_code', 'version']);
            $table->foreign('shot_id')->references('id')->on('shots')->onDelete('cascade');
        });

        // 5. 反馈表
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->enum('target_type', ['asset_version', 'shot_version'])->comment('反馈目标类型');
            $table->bigInteger('target_id')->comment('目标ID');
            $table->string('feedback_folder', 500)->nullable()->comment('反馈文件夹路径');
            $table->json('files')->nullable()->comment('反馈文件列表');
            $table->text('content')->nullable()->comment('反馈内容');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->bigInteger('created_by')->nullable();
            $table->bigInteger('assigned_to')->nullable()->comment('分配给谁');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });

        // 6. 参考素材表
        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['project', 'asset', 'shot', 'step'])->comment('范围');
            $table->bigInteger('target_id')->nullable()->comment('目标ID');
            $table->string('name', 200)->comment('素材名称');
            $table->string('file_path', 500)->comment('文件路径');
            $table->string('file_type', 50)->nullable()->comment('文件类型');
            $table->bigInteger('file_size')->nullable();
            $table->text('description')->nullable();
            $table->json('tags')->nullable()->comment('标签');
            $table->bigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['scope', 'target_id']);
        });

        // 7. 版本依赖关系表
        Schema::create('version_dependencies', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['asset_version', 'shot_version'])->comment('源版本类型');
            $table->bigInteger('source_id')->comment('源版本ID');
            $table->enum('target_type', ['asset_version', 'shot_version'])->comment('目标版本类型');
            $table->bigInteger('target_id')->comment('目标版本ID');
            $table->enum('dependency_type', ['reference', 'input', 'output'])->comment('依赖类型');
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['target_type', 'target_id']);
        });

        // 8. 文件变更日志表（用于文件监控）
        Schema::create('file_change_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('project_id');
            $table->string('file_path', 500)->comment('文件路径');
            $table->enum('change_type', ['created', 'modified', 'deleted', 'renamed'])->comment('变更类型');
            $table->bigInteger('file_size')->nullable();
            $table->string('file_hash', 64)->nullable()->comment('文件哈希');
            $table->enum('sync_status', ['pending', 'synced', 'ignored'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('detected_at')->nullable()->comment('检测时间');
            $table->timestamps();

            $table->index(['project_id', 'sync_status']);
            $table->index('file_path');
        });

        // 9. 命名规则检查日志表
        Schema::create('naming_check_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('target_type', ['asset_version', 'shot_version']);
            $table->bigInteger('target_id');
            $table->string('file_path', 500);
            $table->enum('check_result', ['pass', 'warning', 'error'])->comment('检查结果');
            $table->json('issues')->nullable()->comment('问题列表');
            $table->timestamps();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('naming_check_logs');
        Schema::dropIfExists('file_change_logs');
        Schema::dropIfExists('version_dependencies');
        Schema::dropIfExists('references');
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('shot_versions');
        Schema::dropIfExists('shots');
        Schema::dropIfExists('pipeline_steps');
        Schema::dropIfExists('path_templates');
    }
}
