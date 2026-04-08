<?php

namespace App\Http\Controllers\Api;

use App\Module\AssetLibrary;
use App\Module\Base;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetVersion;
use App\Models\AssetTask;
use App\Models\User;
use App\Models\PipelineStep;
use App\Models\PathTemplate;
use Illuminate\Http\Request;
use Request as RequestFacade;

/**
 * @apiDefine assets
 *
 * 资产管理
 */
class AssetsController extends AbstractController
{
    protected $assetLibrary;

    public function __construct()
    {
        $this->assetLibrary = new AssetLibrary();
    }

    /**
     * @api {get} api/assets          01. 资产列表
     *
     * @apiDescription 获取资产列表，支持过滤、搜索、分页
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__lists
     *
     * @apiParam {Number} [project_id]      项目ID
     * @apiParam {Number} [category_id]     分类ID
     * @apiParam {String} [status]          状态：draft/review/approved/archived
     * @apiParam {String} [keyword]         搜索关键词
     * @apiParam {String} [tags]            标签（逗号分隔）
     * @apiParam {Number} [page]            页码
     * @apiParam {Number} [per_page]        每页数量
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function lists()
    {
        $user = User::auth();

        $query = Asset::with(['category', 'project', 'creator']);

        // 过滤
        if (RequestFacade::input('project_id')) {
            $query->where('project_id', RequestFacade::input('project_id'));
        }

        if (RequestFacade::input('category_id')) {
            $query->where('category_id', RequestFacade::input('category_id'));
        }

        if (RequestFacade::input('status')) {
            $query->where('status', RequestFacade::input('status'));
        }

        // 搜索
        if (RequestFacade::input('keyword')) {
            $keyword = RequestFacade::input('keyword');
            $query->where(function($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // 标签过滤
        if (RequestFacade::input('tags')) {
            $tags = explode(',', RequestFacade::input('tags'));
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', trim($tag));
            }
        }

        // 排序
        $sortBy = RequestFacade::input('sort_by', 'created_at');
        $sortOrder = RequestFacade::input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分页
        $perPage = min(RequestFacade::input('per_page', 20), 100);
        $assets = $query->paginate($perPage);

        return Base::retSuccess('success', $assets);
    }

    /**
     * @api {get} api/assets/:id          02. 资产详情
     *
     * @apiDescription 获取资产详情（含所有版本）
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__detail
     *
     * @apiParam {Number} id                资产ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function detail()
    {
        $id = intval(RequestFacade::input('id'));

        $asset = Asset::with([
            'category',
            'project',
            'creator',
            'versions' => function($query) {
                $query->with('creator')->orderBy('version', 'desc');
            },
            'tasks' => function($query) {
                $query->with(['task', 'assignee']);
            }
        ])->findOrFail($id);

        return Base::retSuccess('success', $asset);
    }

    /**
     * @api {post} api/assets          03. 创建资产
     *
     * @apiDescription 创建资产并自动生成目录结构
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__store
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {Number} category_id       分类ID
     * @apiParam {String} name              资产名称
     * @apiParam {String} code              资产编码
     * @apiParam {String} [description]     描述
     * @apiParam {Array} [tags]             标签
     * @apiParam {Boolean} [create_structure] 是否创建目录结构（默认true）
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function store()
    {
        $user = User::auth();

        RequestFacade::validate([
            'project_id' => 'required|exists:projects,id',
            'category_id' => 'required|exists:asset_categories,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:100',
        ]);

        // 检查编码是否重复
        $exists = Asset::where('project_id', RequestFacade::input('project_id'))
            ->where('code', RequestFacade::input('code'))
            ->exists();

        if ($exists) {
            return Base::retError('资产编码已存在');
        }

        $asset = $this->assetLibrary->createAsset([
            'project_id' => RequestFacade::input('project_id'),
            'category_id' => RequestFacade::input('category_id'),
            'name' => RequestFacade::input('name'),
            'code' => RequestFacade::input('code'),
            'description' => RequestFacade::input('description'),
            'tags' => RequestFacade::input('tags', []),
            'metadata' => RequestFacade::input('metadata', []),
            'create_structure' => RequestFacade::input('create_structure', true),
            'created_by' => $user->userid,
        ]);

        $asset->load(['category', 'project', 'creator']);

        return Base::retSuccess('资产创建成功', $asset);
    }

    /**
     * @api {put} api/assets/:id          04. 更新资产
     *
     * @apiDescription 更新资产信息
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__update
     *
     * @apiParam {Number} id                资产ID
     * @apiParam {String} [name]            资产名称
     * @apiParam {String} [description]     描述
     * @apiParam {String} [status]          状态
     * @apiParam {Array} [tags]             标签
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function update()
    {
        $id = intval(RequestFacade::input('id'));
        $asset = Asset::findOrFail($id);

        $updateData = [];

        if (RequestFacade::has('name')) {
            $updateData['name'] = RequestFacade::input('name');
        }

        if (RequestFacade::has('description')) {
            $updateData['description'] = RequestFacade::input('description');
        }

        if (RequestFacade::has('status')) {
            $updateData['status'] = RequestFacade::input('status');
        }

        if (RequestFacade::has('tags')) {
            $updateData['tags'] = RequestFacade::input('tags');
        }

        if (RequestFacade::has('metadata')) {
            $updateData['metadata'] = RequestFacade::input('metadata');
        }

        $asset->update($updateData);
        $asset->load(['category', 'project', 'creator']);

        return Base::retSuccess('更新成功', $asset);
    }

    /**
     * @api {delete} api/assets/:id          05. 删除资产
     *
     * @apiDescription 删除资产（软删除）
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__destroy
     *
     * @apiParam {Number} id                资产ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     */
    public function destroy()
    {
        $id = intval(RequestFacade::input('id'));
        $asset = Asset::findOrFail($id);
        $asset->update(['status' => 'archived']);

        return Base::retSuccess('删除成功');
    }

    /**
     * @api {post} api/assets/:id/versions          06. 上传版本
     *
     * @apiDescription 上传资产新版本
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__uploadVersion
     *
     * @apiParam {Number} id                资产ID
     * @apiParam {File} file                文件
     * @apiParam {String} sub_type          子类型：model/texture/rig/animation
     * @apiParam {String} [comment]         版本说明
     * @apiParam {Number} [task_id]         关联任务ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function uploadVersion()
    {
        $id = intval(RequestFacade::input('id'));
        $user = User::auth();

        RequestFacade::validate([
            'file' => 'required|file|max:512000', // 500MB
            'step_code' => 'required|string',
            'comment' => 'nullable|string',
        ]);

        $result = $this->assetLibrary->uploadVersion($id, RequestFacade::file('file'), [
            'step_code' => RequestFacade::input('step_code'),
            'comment' => RequestFacade::input('comment'),
            'task_id' => RequestFacade::input('task_id'),
            'created_by' => $user->userid,
        ]);

        if (isset($result['duplicate'])) {
            return Base::retError('文件已存在', $result['version']);
        }

        $result->load(['asset', 'creator']);

        return Base::retSuccess('版本上传成功', $result);
    }

    /**
     * @api {get} api/assets/:id/versions          07. 版本列表
     *
     * @apiDescription 获取资产版本列表
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__versions
     *
     * @apiParam {Number} id                资产ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function versions()
    {
        $id = intval(RequestFacade::input('id'));

        $versions = AssetVersion::where('asset_id', $id)
            ->with('creator')
            ->orderBy('version', 'desc')
            ->get();

        return Base::retSuccess('success', $versions);
    }

    /**
     * @api {get} api/assets/:id/versions/:version/download          08. 下载版本
     *
     * @apiDescription 下载资产版本文件
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__downloadVersion
     *
     * @apiParam {Number} id                资产ID
     * @apiParam {Number} version           版本号
     *
     * @apiSuccess {File} file              文件流
     */
    public function downloadVersion()
    {
        $id = intval(RequestFacade::input('id'));
        $version = intval(RequestFacade::input('version'));

        $assetVersion = AssetVersion::where('asset_id', $id)
            ->where('version', $version)
            ->firstOrFail();

        return $this->assetLibrary->downloadVersion($assetVersion->id);
    }

    /**
     * @api {post} api/assets/:id/tasks          09. 关联任务
     *
     * @apiDescription 将资产关联到任务
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__attachTask
     *
     * @apiParam {Number} id                资产ID
     * @apiParam {Number} task_id           任务ID
     * @apiParam {String} role              制作角色
     * @apiParam {Number} [assigned_to]     分配给谁
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function attachTask()
    {
        $id = intval(RequestFacade::input('id'));

        RequestFacade::validate([
            'task_id' => 'required|exists:project_tasks,id',
            'role' => 'required|in:modeling,texturing,rigging,animation,lighting,compositing,other',
        ]);

        $assetTask = AssetTask::updateOrCreate(
            [
                'asset_id' => $id,
                'task_id' => RequestFacade::input('task_id'),
            ],
            [
                'role' => RequestFacade::input('role'),
                'assigned_to' => RequestFacade::input('assigned_to'),
                'status' => 'pending',
                'created_at' => now(),
            ]
        );

        $assetTask->load(['asset', 'task', 'assignee']);

        return Base::retSuccess('关联成功', $assetTask);
    }

    /**
     * @api {get} api/assets/projects          10. 项目列表
     *
     * @apiDescription 获取用户有权限的项目列表
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__projects
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function projects()
    {
        $user = User::auth();

        // 获取用户有权限的项目列表
        $projects = \App\Models\Project::select([
            'id',
            'name',
            'desc as description',
            'archived_at',
            'created_at',
            'updated_at',
            'storage_root',
            'storage_type',
            'project_code',
            'asset_template_id',
            'shot_template_id',
        ])
        ->whereIn('id', function($query) use ($user) {
            $query->select('project_id')
                ->from('project_users')
                ->where('userid', $user->userid);
        })
        ->whereNull('archived_at')
        ->orderBy('created_at', 'desc')
        ->get();

        return Base::retSuccess('success', $projects);
    }

    /**
     * @api {get} api/assets/categories          11. 分类列表
     *
     * @apiDescription 获取资产分类列表
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__categories
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function categories()
    {
        $categories = AssetCategory::orderBy('sort')->get();

        return Base::retSuccess('success', $categories);
    }

    /**
     * @api {get} api/assets/pipeline-steps          12. 流程步骤列表
     *
     * @apiDescription 获取项目的流程步骤列表
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__pipelineSteps
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} [type]            类型：asset/shot/both
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function pipelineSteps()
    {
        $projectId = intval(RequestFacade::input('project_id'));
        $type = RequestFacade::input('type');

        $query = PipelineStep::where('project_id', $projectId)
            ->where('is_active', true);

        if ($type) {
            $query->where(function($q) use ($type) {
                $q->where('type', $type)->orWhere('type', 'both');
            });
        }

        $steps = $query->orderBy('sort')->get();

        // 项目未初始化流程时，自动创建默认流程
        if ($projectId > 0 && $steps->isEmpty()) {
            \Database\Seeders\PipelineStepSeeder::createForProject($projectId);
            $steps = $query->orderBy('sort')->get();
        }

        return Base::retSuccess('success', $steps);
    }

    /**
     * @api {get} api/assets/path-templates          13. 路径模板列表
     *
     * @apiDescription 获取路径模板列表
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__pathTemplates
     *
     * @apiParam {String} [type]            类型：asset/shot
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function pathTemplates()
    {
        $type = RequestFacade::input('type');

        $query = PathTemplate::query();

        if ($type) {
            $query->where('type', $type);
        }

        $templates = $query->orderBy('is_system', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Base::retSuccess('success', $templates);
    }

    /**
     * @api {get} api/assets/:id/local-path          14. 获取本地路径
     *
     * @apiDescription 获取资产的本地绝对路径
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__localPath
     *
     * @apiParam {Number} id                资产ID
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function localPath()
    {
        $user = User::auth();

        $id = intval(RequestFacade::input('id'));
        if (!$id) {
            return Base::retError('资产ID不能为空');
        }

        $asset = Asset::with('project')->find($id);
        if (!$asset) {
            return Base::retError('资产不存在');
        }

        if (!$asset->full_path) {
            return Base::retError('资产路径未设置');
        }

        $absolutePath = $this->assetLibrary->getAbsolutePath($asset->project, $asset->full_path);

        return Base::retSuccess('success', [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'relative_path' => $asset->full_path,
            'absolute_path' => $absolutePath,
            'storage_root' => $asset->project->storage_root ?: '未配置',
            'storage_type' => $asset->project->storage_type ?: 'local',
        ]);
    }

    /**
     * @api {post} api/assets/init-project-pipeline          15. 初始化项目流程
     *
     * @apiDescription 为项目创建默认流程步骤
     * @apiVersion 1.0.0
     * @apiGroup assets
     * @apiName assets__initProjectPipeline
     *
     * @apiParam {Number} project_id        项目ID
     * @apiParam {String} [preset]          预设：film/tv/game
     *
     * @apiSuccess {Number} ret     返回状态码（1正确、0错误）
     * @apiSuccess {String} msg     返回信息（错误描述）
     * @apiSuccess {Object} data    返回数据
     */
    public function debugCreateDir()
 {
 $path = RequestFacade::input('path', 'H:\\2026\\Assets\\prop\\11111');
 $result = $this->assetLibrary->verifyDirectoryCreation($path);

 return Base::retSuccess('目录创建成功', $result);
 }

 public function mountStatus()
 {
 $path = trim((string)RequestFacade::input('path', ''));
 if ($path === '') {
 return Base::retError('路径不能为空');
 }

 $status = $this->assetLibrary->getPathMountStatus($path);
 return Base::retSuccess('success', $status);
 }

 public function mountShare()
 {
 RequestFacade::validate([
 'drive_letter' => 'required|string|size:1',
 'host' => 'required|string|max:255',
 'share' => 'required|string|max:255',
 'username' => 'required|string|max:255',
 'password' => 'required|string|max:255',
 ]);

 $result = $this->assetLibrary->mountWindowsShare([
 'drive_letter' => RequestFacade::input('drive_letter'),
 'host' => RequestFacade::input('host'),
 'share' => RequestFacade::input('share'),
 'username' => RequestFacade::input('username'),
 'password' => RequestFacade::input('password'),
 ]);

 return Base::retSuccess('挂载成功', $result);
 }

 public function initProjectPipeline()
    {
        $projectId = intval(RequestFacade::input('project_id'));
        $preset = RequestFacade::input('preset', 'film');

        // 检查是否已初始化
        $exists = PipelineStep::where('project_id', $projectId)->exists();
        if ($exists) {
            return Base::retError('项目流程已初始化');
        }

        // 创建默认流程
        \Database\Seeders\PipelineStepSeeder::createForProject($projectId);

        $steps = PipelineStep::where('project_id', $projectId)->get();

        return Base::retSuccess('流程初始化成功', $steps);
    }
}
