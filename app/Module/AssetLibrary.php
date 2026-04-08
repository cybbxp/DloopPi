<?php

namespace App\Module;

use App\Exceptions\ApiException;
use App\Models\Asset;
use App\Models\AssetVersion;
use App\Models\AssetCategory;
use App\Models\Project;
use App\Models\PathTemplate;
use App\Models\PipelineStep;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * 资产库管理模块
 *
 * 核心功能：
 * 1. 相对路径管理
 * 2. History 机制（当前文件无版本号）
 * 3. 路径模板解析
 * 4. 自动目录创建
 */
class AssetLibrary
{
    /**
     * 创建资产
     */
    public function createAsset($data)
    {
        $project = Project::findOrFail($data['project_id']);
        $category = AssetCategory::findOrFail($data['category_id']);

        // 1. 生成 path_name（用于路径的资产名称）
        $pathName = $this->sanitizePathName($data['name']);

        // 2. 生成相对路径
        $relativePath = $this->generateAssetPath($project, $category, $pathName);

        // 3. 创建数据库记录
        $asset = Asset::create([
            'project_id' => $data['project_id'],
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'code' => $data['code'] ?? $this->generateAssetCode($category, $pathName),
            'path_name' => $pathName,
            'full_path' => $relativePath,
            'description' => $data['description'] ?? null,
            'tags' => $data['tags'] ?? [],
            'metadata' => $data['metadata'] ?? [],
            'created_by' => $data['created_by'],
        ]);

        // 4. 创建物理目录结构
        $createdDirectories = [];
        if ($data['create_structure'] ?? true) {
            $createdDirectories = $this->createAssetDirectories($asset);
        }

        $asset->setAttribute('created_directories', $createdDirectories);
        $asset->setAttribute('resolved_absolute_path', $this->toRuntimePath($this->getAbsolutePath($project, $asset->full_path)));

        return $asset;
    }

    /**
     * 生成资产相对路径
     *
     * 例如：Assets/characters/HeroA
     */
    private function generateAssetPath(Project $project, AssetCategory $category, string $pathName): string
    {
        // 如果项目配置了路径模板，使用模板
        if ($project->asset_template_id) {
            $template = PathTemplate::find($project->asset_template_id);
            if ($template) {
                return $template->resolvePath([
                    'category' => ['code' => $category->code, 'name' => $category->name],
                    'asset' => ['path_name' => $pathName],
                ]);
            }
        }

        // 默认路径：Assets/{category_code}/{asset_path_name}
        return "Assets/{$category->code}/{$pathName}";
    }

    /**
     * 创建资产目录结构
     *
     * 为每个流程步骤创建目录，并创建 history, preview, feedback 子目录
     */
    public function createAssetDirectories(Asset $asset)
    {
        $project = $asset->project;
        $absoluteBasePath = $this->toRuntimePath($this->getAbsolutePath($project, $asset->full_path));

        // 获取项目的资产流程步骤
        $steps = PipelineStep::where('project_id', $project->id)
            ->where('type', 'asset')
            ->orderBy('sort')
            ->get();

        // 项目未初始化流程时，自动创建默认流程后再继续建目录
        if ($steps->isEmpty()) {
            \Database\Seeders\PipelineStepSeeder::createForProject($project->id);
            $steps = PipelineStep::where('project_id', $project->id)
                ->where('type', 'asset')
                ->orderBy('sort')
                ->get();
        }

        $created = [];
        $this->ensureManagedDirectory($absoluteBasePath);

        foreach ($steps as $step) {
            // 创建流程目录，如：Assets/characters/HeroA/Model
            $stepPath = "{$absoluteBasePath}/{$step->name}";

            if (!File::exists($stepPath)) {
                $this->ensureManagedDirectory($stepPath);
                $created[] = "{$asset->full_path}/{$step->name}";
            }

            // 创建子目录：history, preview, feedback
            $subfolders = $step->getSubfolders();
            foreach ($subfolders as $subfolder) {
                $subPath = "{$stepPath}/{$subfolder}";
                if (!File::exists($subPath)) {
                    $this->ensureManagedDirectory($subPath);
                    $created[] = "{$asset->full_path}/{$step->name}/{$subfolder}";
                }
            }
        }

        return $created;
    }

    /**
     * 上传新版本（History 机制）
     *
     * 流程：
     * 1. 如果存在当前版本，将其移入 history 并重命名（加版本号）
     * 2. 保存新文件为当前版本（无版本号）
     * 3. 更新数据库记录
     */
    public function uploadVersion($assetId, $file, $data)
    {
        $asset = Asset::with(['project', 'category'])->findOrFail($assetId);
        $project = $asset->project;
        $stepCode = $data['step_code'] ?? 'model';

        // 1. 获取文件信息
        $originalName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $extension = $file->getClientOriginalExtension();

        try {
            $mimeType = $file->getMimeType();
        } catch (\Exception $e) {
            $mimeType = 'application/octet-stream';
        }

        // 2. 计算新版本号（按资产全局递增，避免与 uk_asset_version 冲突）
        $latestVersion = AssetVersion::where('asset_id', $assetId)
            ->max('version') ?? 0;
        $newVersion = $latestVersion + 1;

        // 3. 生成路径
        $step = PipelineStep::where('project_id', $project->id)
            ->where('code', $stepCode)
            ->first();

        $stepName = $step ? $step->name : ucfirst($stepCode);

        // 当前文件路径（无版本号）：Assets/characters/HeroA/Model/HeroA_Model.ma
        $currentFileName = "{$asset->path_name}_{$stepName}.{$extension}";
        $currentRelativePath = "{$asset->full_path}/{$stepName}/{$currentFileName}";
        $currentAbsolutePath = $this->toRuntimePath($this->getAbsolutePath($project, $currentRelativePath));

        // History 文件路径（有版本号）：Assets/characters/HeroA/Model/history/HeroA_Model_v003.ma
        $historyFileName = "{$asset->path_name}_{$stepName}_v" . str_pad($newVersion, 3, '0', STR_PAD_LEFT) . ".{$extension}";
        $historyRelativePath = "{$asset->full_path}/{$stepName}/history/{$historyFileName}";
        $historyAbsolutePath = $this->toRuntimePath($this->getAbsolutePath($project, $historyRelativePath));

        // 4. 如果存在当前版本，移入 history
        if (File::exists($currentAbsolutePath)) {
            // 获取当前版本的版本号
            $currentVersion = AssetVersion::where('asset_id', $assetId)
                ->where('step_code', $stepCode)
                ->where('is_current', true)
                ->first();

            if ($currentVersion) {
                // 重命名当前文件到 history
                $oldHistoryFileName = "{$asset->path_name}_{$stepName}_v" . str_pad($currentVersion->version, 3, '0', STR_PAD_LEFT) . ".{$extension}";
                $oldHistoryPath = $this->toRuntimePath($this->getAbsolutePath($project, "{$asset->full_path}/{$stepName}/history/{$oldHistoryFileName}"));

                $this->ensureManagedDirectory(dirname($oldHistoryPath));
                File::move($currentAbsolutePath, $oldHistoryPath);

                // 更新数据库记录
                $currentVersion->update([
                    'is_current' => false,
                    'file_path_history' => "{$asset->full_path}/{$stepName}/history/{$oldHistoryFileName}",
                ]);
            }
        }

        // 5. 保存新文件为当前版本
        $this->ensureManagedDirectory(dirname($currentAbsolutePath));
        $file->move(dirname($currentAbsolutePath), $currentFileName);

        // 6. 计算文件哈希
        $hash = hash_file('sha256', $currentAbsolutePath);

        // 7. 创建版本记录
        $version = AssetVersion::create([
            'asset_id' => $assetId,
            'step_code' => $stepCode,
            'version' => $newVersion,
            'is_current' => true,
            'file_path' => $currentRelativePath,  // 兼容旧版
            'file_path_current' => $currentRelativePath,
            'file_path_history' => $historyRelativePath,  // 预留，实际文件还在 current
            'file_name' => $originalName,
            'file_size' => $fileSize,
            'file_hash' => $hash,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'comment' => $data['comment'] ?? null,
            'status' => 'approved',
            'created_by' => $data['created_by'],
        ]);

        // 8. 更新资产最新版本号
        $asset->update(['latest_version' => $newVersion]);

        return $version;
    }

    /**
     * 下载版本
     */
    public function downloadVersion($versionId)
    {
        $version = AssetVersion::with(['asset.project'])->findOrFail($versionId);

        // 如果是当前版本，下载 file_path_current
        $relativePath = $version->is_current ? $version->file_path_current : $version->file_path_history;

        if (!$relativePath) {
            $relativePath = $version->file_path;
        }

        $absolutePath = $this->toRuntimePath($this->getAbsolutePath($version->asset->project, $relativePath));

        if (!File::exists($absolutePath)) {
            throw new ApiException('文件不存在: ' . $absolutePath, [], 0, false);
        }

        return response()->download($absolutePath, $version->file_name);
    }

    /**
     * 获取绝对路径
     *
     * @param Project $project
     * @param string $relativePath
     * @return string
     */
    public function getAbsolutePath(Project $project, string $relativePath): string
    {
        $rootPath = $project->storage_root;

        // 如果项目未配置存储根路径，使用默认路径
        if (!$rootPath) {
            $rootPath = storage_path('assets/projects');
        }

        $projectFolder = trim((string)($project->name ?: $project->id));
        $projectFolder = preg_replace('~[\\/:*?"<>|]~', '_', $projectFolder);
        $relativePath = ltrim($relativePath, '/\\');

        // 处理 Windows 路径
        if (preg_match('/^[A-Z]:/i', $rootPath)) {
            $normalizedRoot = rtrim(str_replace('/', '\\', $rootPath), '\\');
            $lastSegment = basename(str_replace('\\', '/', $normalizedRoot));
            $base = strcasecmp($lastSegment, $projectFolder) === 0
                ? $normalizedRoot
                : $normalizedRoot . '\\' . $projectFolder;
            return $base . '\\' . str_replace('/', '\\', $relativePath);
        }

        // Linux 路径
        $normalizedRoot = rtrim($rootPath, '/');
        $lastSegment = basename($normalizedRoot);
        $base = $lastSegment === $projectFolder
            ? $normalizedRoot
            : $normalizedRoot . '/' . $projectFolder;
        return $base . '/' . $relativePath;
    }

    /**
     * 统一目录检查与创建入口（轻量扩展点）
     */
    private function toRuntimePath(string $path): string
 {
 // Linux 容器内将 Windows盘符路径映射到 /mnt/<drive>
 if (DIRECTORY_SEPARATOR === '/' && preg_match('/^[A-Z]:/i', $path) && !preg_match('/^[A-Z]:[\\\\\/]/i', $path)) {
 throw new ApiException('Windows路径格式错误: ' . $path, [
 'type' => 'invalid_windows_path',
 'input_path' => $path,
],0, false);
 }

 if (DIRECTORY_SEPARATOR === '/' && preg_match('/^[A-Z]:[\\\\\/]/i', $path)) {
 $drive = strtolower($path[0]);
 $driveMount = '/mnt/' . $drive;
 if (!File::isDirectory($driveMount)) {
 throw new ApiException('Windows驱动器未挂载: ' . $path, [
 'type' => 'mount_required',
 'input_path' => $path,
 'drive_letter' => strtoupper($drive),
 'mount_point' => $driveMount,
 ],0, false);
 }
 $tail = ltrim(substr($path,2), '\\/');
 return $driveMount . '/' . str_replace('\\', '/', $tail);
 }

 return $path;
 }

    /**
 *目录创建链路自检（用于快速定位环境问题）
 */
 public function verifyDirectoryCreation(string $absolutePath): array
 {
 $runtimePath = $this->toRuntimePath($absolutePath);
 $this->ensureManagedDirectory($runtimePath);

 return [
 'input_path' => $absolutePath,
 'runtime_path' => $runtimePath,
 'exists' => File::isDirectory($runtimePath),
 ];
 }

 public function getPathMountStatus(string $path): array
 {
 if (!preg_match('/^([A-Z]):[\\\/]/i', $path, $match)) {
 return [
 'path' => $path,
 'is_windows_path' => false,
 'mounted' => true,
 'writable' => is_writable($path),
 'mount_point' => null,
 'drive_letter' => null,
 ];
 }

 $drive = strtolower($match[1]);
 $mountPoint = '/mnt/' . $drive;

 return [
 'path' => $path,
 'is_windows_path' => true,
 'drive_letter' => strtoupper($drive),
 'mount_point' => $mountPoint,
 'mounted' => File::isDirectory($mountPoint),
 'writable' => File::isDirectory($mountPoint) && is_writable($mountPoint),
 ];
 }

 public function mountWindowsShare(array $data): array
 {
 $drive = strtolower($data['drive_letter']);
 if (!preg_match('/^[a-z]$/', $drive)) {
 throw new ApiException('盘符格式错误', [],0, false);
 }

 $host = trim((string)$data['host']);
 $share = trim((string)$data['share']);
 $username = trim((string)$data['username']);
 $password = (string)$data['password'];

 $host = trim(str_replace('/', '\\', $host));
 $share = trim(str_replace('/', '\\', $share));

 if ($host !== '') {
 $host = ltrim($host, '\\');
 if (strpos($host, '\\') !== false) {
 [$hostPart, $sharePart] = explode('\\', $host,2);
 $host = trim($hostPart);
 if ($share === '' && $sharePart !== '') {
 $share = trim($sharePart);
 }
 }
 }

 if ($share !== '' && strpos($share, '\\') !== false) {
 $parts = array_values(array_filter(explode('\\', $share), static fn($v) => $v !== ''));
 $share = trim($parts[0] ?? '');
 }

 $share = trim($share, " \t\n\r\0\x0B\\/");

 if ($host === '' || $share === '' || $username === '' || $password === '') {
 throw new ApiException('挂载参数不完整', [],0, false);
 }

 if (!preg_match('/^[a-zA-Z0-9._-]+$/', $host)) {
 throw new ApiException('主机地址格式错误', [],0, false);
 }

 if (preg_match('/[\\\\\/:*?"<>|]/', $share)) {
 throw new ApiException('共享名称格式错误', [
 'share' => $share,
 ],0, false);
 }

 $mountBinary = trim((string)@shell_exec('command -v mount.cifs2>/dev/null'));
 if ($mountBinary === '') {
 throw new ApiException('系统未安装 cifs-utils（缺少 mount.cifs）', [],0, false);
 }

 $mountPoint = '/mnt/' . $drive;
 $this->ensureManagedDirectory($mountPoint);

 $credentialsDir = storage_path('app/mount_credentials');
 $this->ensureManagedDirectory($credentialsDir);

 $credentialFile = $credentialsDir . '/.' . $drive . '.cred';
 File::put($credentialFile, "username={$username}\npassword={$password}\n");
 @chmod($credentialFile,0600);

 $sharePath = '//' . $host . '/' . $share;
 $options = 'credentials=' . $credentialFile . ',vers=3.0,iocharset=utf8,uid=0,gid=0,file_mode=0775,dir_mode=0775';
 $cmd = sprintf(
 '%s -t cifs %s %s -o %s2>&1',
 escapeshellcmd('mount'),
 escapeshellarg($sharePath),
 escapeshellarg($mountPoint),
 escapeshellarg($options)
 );

 $output = [];
 $code =0;
 exec($cmd, $output, $code);
 @unlink($credentialFile);

 if ($code !==0) {
 throw new ApiException('挂载失败: ' . implode("\n", $output), [],0, false);
 }

 $mounted = File::isDirectory($mountPoint);
 $writable = $mounted && is_writable($mountPoint);
 if (!$mounted || !$writable) {
 throw new ApiException('挂载完成但目录不可写: ' . $mountPoint, [
 'type' => 'mount_not_writable',
 'mount_point' => $mountPoint,
 'mounted' => $mounted,
 'writable' => $writable,
 ],0, false);
 }

 return [
 'drive_letter' => strtoupper($drive),
 'mount_point' => $mountPoint,
 'share_path' => $sharePath,
 'mounted' => $mounted,
 'writable' => $writable,
 ];
 }

 private function ensureManagedDirectory(string $path): void
 {
 $normalizedPath = trim($path);
 if ($normalizedPath === '') {
 return;
 }

 try {
 File::ensureDirectoryExists($normalizedPath);
 } catch (\Throwable $e) {
 $mountStatus = $this->getPathMountStatus($normalizedPath);
 $data = $mountStatus['is_windows_path'] ? [
 'type' => (!$mountStatus['mounted'] ? 'mount_required' : 'mount_not_writable'),
 'input_path' => $normalizedPath,
 'drive_letter' => $mountStatus['drive_letter'],
 'mount_point' => $mountStatus['mount_point'],
 'mounted' => $mountStatus['mounted'],
 'writable' => $mountStatus['writable'],
 ] : [];
 throw new ApiException('目录创建失败: ' . $normalizedPath, $data,0, false);
 }

 if (!File::isDirectory($normalizedPath)) {
 throw new ApiException('目录创建失败: ' . $normalizedPath, [],0, false);
 }

 if (!is_writable($normalizedPath)) {
 $mountStatus = $this->getPathMountStatus($normalizedPath);
 $data = $mountStatus['is_windows_path'] ? [
 'type' => (!$mountStatus['mounted'] ? 'mount_required' : 'mount_not_writable'),
 'input_path' => $normalizedPath,
 'drive_letter' => $mountStatus['drive_letter'],
 'mount_point' => $mountStatus['mount_point'],
 'mounted' => $mountStatus['mounted'],
 'writable' => $mountStatus['writable'],
 ] : [];
 throw new ApiException('目录不可写: ' . $normalizedPath, $data,0, false);
 }
 }

 /**
 * 清理路径名称（去除特殊字符）
 */
    private function sanitizePathName(string $name): string
    {
        // 保留中文、英文、数字、下划线、连字符
        $name = preg_replace('/[^\p{L}\p{N}_-]/u', '_', $name);
        return trim($name, '_');
    }

    /**
     * 生成资产代码
     */
    private function generateAssetCode(AssetCategory $category, string $pathName): string
    {
        $prefix = strtoupper(substr($category->code, 0, 2));
        $timestamp = date('ymd');
        $random = strtoupper(Str::random(4));

        return "{$prefix}_{$timestamp}_{$random}";
    }

    /**
     * 批量创建项目资产结构
     */
    public function batchCreateStructure($projectId, $template, $createdBy)
    {
        $created = [];

        foreach ($template as $item) {
            $category = AssetCategory::where('code', $item['category'])->first();

            if (!$category) {
                continue;
            }

            $asset = $this->createAsset([
                'project_id' => $projectId,
                'category_id' => $category->id,
                'name' => $item['name'],
                'code' => $item['code'] ?? null,
                'description' => $item['description'] ?? null,
                'tags' => $item['tags'] ?? [],
                'create_structure' => true,
                'created_by' => $createdBy,
            ]);

            $created[] = $asset;
        }

        return $created;
    }

    /**
     * 删除资产版本
     */
    public function deleteVersion($versionId)
    {
        $version = AssetVersion::with(['asset.project'])->findOrFail($versionId);

        // 删除文件
        if ($version->is_current && $version->file_path_current) {
            $path = $this->toRuntimePath($this->getAbsolutePath($version->asset->project, $version->file_path_current));
            if (File::exists($path)) {
                File::delete($path);
            }
        } elseif ($version->file_path_history) {
            $path = $this->toRuntimePath($this->getAbsolutePath($version->asset->project, $version->file_path_history));
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        // 删除预览图
        if ($version->preview_path) {
            $previewPath = $this->toRuntimePath($this->getAbsolutePath($version->asset->project, $version->preview_path));
            if (File::exists($previewPath)) {
                File::delete($previewPath);
            }
        }

        $version->delete();

        return true;
    }

    /**
     * 获取资产的所有版本（按流程分组）
     */
    public function getAssetVersionsByStep($assetId)
    {
        $versions = AssetVersion::where('asset_id', $assetId)
            ->orderBy('step_code')
            ->orderBy('version', 'desc')
            ->get();

        return $versions->groupBy('step_code');
    }
}
