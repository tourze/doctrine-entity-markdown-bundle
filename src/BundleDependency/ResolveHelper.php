<?php

declare(strict_types=1);

namespace Tourze\BundleDependency;

/**
 * Bundle依赖解析帮助类
 * 简化实现，用于解决测试环境中的依赖问题
 */
class ResolveHelper
{
    /**
     * 解析单个Bundle的依赖关系
     *
     * @param string $bundleClass Bundle 类名
     * @return array<string, mixed> Bundle依赖配置，默认返回全启用配置
     */
    private static function resolveSingleBundleDependencies(string $bundleClass): array
    {
        // 检查是否是有效的 Bundle 类
        if (!class_exists($bundleClass)) {
            return ['all' => true];
        }

        // 检查是否有静态方法获取依赖列表
        if (self::hasBundleDependenciesMethod($bundleClass)) {
            return self::getBundleDependenciesFromClass($bundleClass);
        }

        return ['all' => true];
    }

    /**
     * 检查类是否有有效的 getBundleDependencies 方法
     */
    private static function hasBundleDependenciesMethod(string $bundleClass): bool
    {
        return method_exists($bundleClass, 'getBundleDependencies');
    }

    /**
     * 从类获取依赖配置
     *
     * @param string $bundleClass Bundle 类名
     * @return array<string, mixed> Bundle依赖配置
     */
    private static function getBundleDependenciesFromClass(string $bundleClass): array
    {
        try {
            // 使用类型安全的调用方式
            $method = new \ReflectionMethod($bundleClass, 'getBundleDependencies');
            if ($method->isStatic() && $method->isPublic()) {
                $dependencies = $method->invoke(null);
                if (is_array($dependencies)) {
                    return self::ensureStringKeyedArray($dependencies);
                }
            }
        } catch (\Throwable) {
            // 忽略错误，使用默认配置
        }

        return ['all' => true];
    }

    /**
     * 递归添加Bundle及其依赖
     *
     * @param string $bundleClass Bundle 类名
     * @param array<string, array<string, mixed>> $resolved 已解析的依赖
     * @return void
     */
    private static function addBundleWithDependencies(string $bundleClass, array &$resolved): void
    {
        if (self::isAlreadyProcessed($bundleClass, $resolved)) {
            return; // 已处理过，避免循环
        }

        // 获取该Bundle的依赖配置
        $dependencies = self::resolveSingleBundleDependencies($bundleClass);
        $resolved[$bundleClass] = $dependencies;

        // 递归处理依赖
        self::processDependencies($dependencies, $resolved);
    }

    /**
     * 检查Bundle是否已处理
     *
     * @param string $bundleClass Bundle类名
     * @param array<string, mixed> $resolved 已解析的依赖
     * @return bool
     */
    private static function isAlreadyProcessed(string $bundleClass, array $resolved): bool
    {
        return isset($resolved[$bundleClass]);
    }

    /**
     * 处理Bundle的依赖关系
     *
     * @param array<string, mixed> $dependencies 依赖配置
     * @param array<string, array<string, mixed>> $resolved 已解析的依赖
     * @return void
     */
    private static function processDependencies(array $dependencies, array &$resolved): void
    {
        foreach ($dependencies as $depClass => $depConfig) {
            if (self::isValidDependencyClass($depClass)) {
                self::addBundleWithDependencies($depClass, $resolved);
            }
        }
    }

    /**
     * 检查是否为有效的依赖类
     *
     * @param mixed $depClass 依赖类
     * @return bool
     */
    private static function isValidDependencyClass(mixed $depClass): bool
    {
        return is_string($depClass) && class_exists($depClass);
    }

    /**
     * 解析Bundle依赖关系
     *
     * @param array<string, mixed> $bundles Bundle配置数组
     * @return array<string, array<string, mixed>> 解析后的Bundle依赖数组
     */
    public static function resolveBundleDependencies(array $bundles): array
    {
        $resolved = [];

        foreach ($bundles as $bundleClass => $config) {
            if (self::isValidBundleClass($bundleClass)) {
                self::addBundleWithDependencies($bundleClass, $resolved);
            }
        }

        /** @var array<string, array<string, mixed>> $resolved */
        return $resolved;
    }

    /**
     * 检查是否为有效的Bundle类
     *
     * @param mixed $bundleClass Bundle类
     * @return bool
     */
    private static function isValidBundleClass(mixed $bundleClass): bool
    {
        return is_string($bundleClass) && class_exists($bundleClass);
    }

    /**
     * 确保数组是字符串键的数组
     *
     * @param array<mixed, mixed> $array 输入数组
     * @return array<string, mixed> 字符串键的数组
     */
    private static function ensureStringKeyedArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}