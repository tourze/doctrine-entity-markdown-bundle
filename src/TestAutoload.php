<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle;

/**
 * 测试自动加载器
 * 动态加载缺失的依赖类
 */
class TestAutoload
{
    public static function load(): void
    {
        // 确保TestCaseHelper可用
        if (!class_exists('Tourze\PHPUnitBase\TestCaseHelper')) {
            require_once __DIR__ . '/PHPUnitBase/TestCaseHelper.php';
        }

        // 确保ResolveHelper可用
        if (!class_exists('Tourze\BundleDependency\ResolveHelper')) {
            require_once __DIR__ . '/BundleDependency/ResolveHelper.php';
        }
    }
}