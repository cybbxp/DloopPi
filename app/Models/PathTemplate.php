<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 路径模板模型
 *
 * 用于定义资产和镜头的目录结构模板
 */
class PathTemplate extends Model
{
    protected $fillable = [
        'name',
        'type',
        'structure',
        'naming_rules',
        'subfolders',
        'is_system',
        'description',
    ];

    protected $casts = [
        'structure' => 'array',
        'naming_rules' => 'array',
        'subfolders' => 'array',
        'is_system' => 'boolean',
    ];

    /**
     * 使用此模板的项目（资产模板）
     */
    public function projectsAsAssetTemplate()
    {
        return $this->hasMany(Project::class, 'asset_template_id');
    }

    /**
     * 使用此模板的项目（镜头模板）
     */
    public function projectsAsShotTemplate()
    {
        return $this->hasMany(Project::class, 'shot_template_id');
    }

    /**
     * 解析路径
     *
     * @param array $context 上下文数据
     * @return string
     */
    public function resolvePath(array $context): string
    {
        $parts = [];

        foreach ($this->structure as $level) {
            if (isset($level['fixed']) && $level['fixed']) {
                // 固定路径部分
                $parts[] = $level['name'];
            } elseif (isset($level['source'])) {
                // 动态路径部分，从上下文获取
                $value = $this->getValueFromContext($context, $level['source']);
                if ($value || !($level['optional'] ?? false)) {
                    $parts[] = $value ?: $level['name'];
                }
            }
        }

        return implode('/', $parts);
    }

    /**
     * 从上下文获取值
     *
     * @param array $context
     * @param string $source 如 "asset.category.code"
     * @return mixed
     */
    private function getValueFromContext(array $context, string $source)
    {
        $keys = explode('.', $source);
        $value = $context;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } elseif (is_object($value) && isset($value->$key)) {
                $value = $value->$key;
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * 生成文件名
     *
     * @param string $ruleKey 规则键名
     * @param array $context 上下文数据
     * @return string
     */
    public function generateFileName(string $ruleKey, array $context): string
    {
        if (!isset($this->naming_rules[$ruleKey])) {
            return '';
        }

        $template = $this->naming_rules[$ruleKey];

        // 替换占位符：{asset_name}_{step}.{ext}
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($context) {
            $placeholder = $matches[1];

            // 处理格式化：{version:03d}
            if (strpos($placeholder, ':') !== false) {
                [$key, $format] = explode(':', $placeholder, 2);
                $value = $context[$key] ?? '';
                return sprintf("%{$format}", $value);
            }

            return $context[$placeholder] ?? '';
        }, $template);
    }
}
