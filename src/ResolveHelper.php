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
     * 解析Bundle依赖关系
     *
     * @param array<string, mixed> $bundles Bundle配置数组
     * @return array<string, array<string, mixed>> 解析后的Bundle依赖数组
     */
    public static function resolveBundleDependencies(array $bundles): array
    {
        /** @var array<string, array<string, mixed>> $resolved */
        $resolved = [];

        foreach ($bundles as $bundleClass => $config) {
            if (self::isValidBundleClass($bundleClass)) {
                self::processBundle($bundleClass, $resolved);
            }
        }

        return $resolved;
    }

    /**
     * 检查是否为有效的Bundle类
     */
    private static function isValidBundleClass(mixed $bundleClass): bool
    {
        return is_string($bundleClass) && class_exists($bundleClass);
    }

    /**
     * 处理单个Bundle的依赖
     *
     * @param string $bundleClass Bundle类名
     * @param array<string, array<string, mixed>> $resolved 已解析的依赖
     */
    private static function processBundle(string $bundleClass, array &$resolved): void
    {
        if (self::hasDependencyMethod($bundleClass)) {
            self::processBundleWithDependencies($bundleClass, $resolved);
        } else {
            $resolved[$bundleClass] = ['all' => true];
        }
    }

    /**
     * 检查Bundle是否有依赖方法
     */
    private static function hasDependencyMethod(string $bundleClass): bool
    {
        return method_exists($bundleClass, 'getBundleDependencies');
    }

    /**
     * 处理有依赖方法的Bundle
     *
     * @param string $bundleClass Bundle类名
     * @param array<string, array<string, mixed>> $resolved 已解析的依赖
     */
    private static function processBundleWithDependencies(string $bundleClass, array &$resolved): void
    {
        try {
            $dependencies = $bundleClass::getBundleDependencies();
            if (is_array($dependencies)) {
                $resolved[$bundleClass] = ['all' => true];
                $typedDependencies = self::ensureStringKeyedArray($dependencies);
                self::processDependencies($typedDependencies, $resolved);
            } else {
                $resolved[$bundleClass] = ['all' => true];
            }
        } catch (\Throwable) {
            $resolved[$bundleClass] = ['all' => true];
        }
    }

    /**
     * 处理依赖列表
     *
     * @param array<string, mixed> $dependencies 依赖列表
     * @param array<string, array<string, mixed>> $resolved 已解析的依赖
     */
    private static function processDependencies(array $dependencies, array &$resolved): void
    {
        foreach ($dependencies as $depClass => $depConfig) {
            if (self::isValidDependencyClass($depClass)) {
                $config = is_array($depConfig) ? $depConfig : ['all' => true];
                /** @var array<string, mixed> $typedConfig */
                $typedConfig = $config;
                $resolved[$depClass] = $typedConfig;
            }
        }
    }

    /**
     * 检查是否为有效的依赖类
     */
    private static function isValidDependencyClass(mixed $depClass): bool
    {
        return is_string($depClass) && class_exists($depClass);
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