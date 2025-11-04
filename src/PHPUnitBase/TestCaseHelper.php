<?php

declare(strict_types=1);

namespace Tourze\PHPUnitBase;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;

/**
 * PHPUnit测试用例帮助类
 * 简化实现，用于解决测试环境中的依赖问题
 */
class TestCaseHelper
{
    /**
     * 从测试类中提取被测试的目标类
     *
     * @param ReflectionClass<object> $reflection 测试类的反射
     * @return class-string|null 被测试的目标类名
     */
    public static function extractCoverClass(ReflectionClass $reflection): ?string
    {
        // 查找 #[CoversClass] 注解
        $attributes = $reflection->getAttributes('PHPUnit\Framework\Attributes\CoversClass');

        foreach ($attributes as $attribute) {
            $args = $attribute->getArguments();
            if (isset($args[0]) && is_string($args[0]) && class_exists($args[0])) {
                return $args[0];
            }
        }

        // 如果没有找到注解，尝试从类名推断
        $className = $reflection->getName();

        // 如果测试类名以 "Test" 结尾，去掉后缀作为目标类名
        if (str_ends_with($className, 'Test')) {
            $targetClass = substr($className, 0, -4);
            // 确保目标类不是测试类本身，并且目标类存在
            if ($targetClass !== $className && class_exists($targetClass)) {
                return $targetClass;
            }
        }

        return null;
    }

    /**
     * 检查给定的类名是否为测试类
     */
    public static function isTestClass(string $className): bool
    {
        return str_ends_with($className, 'Test')
            || str_ends_with($className, 'TestCase')
            || str_contains($className, '\\Tests\\');
    }

    /**
     * 检查给定的类是否存在并且可以实例化
     */
    public static function classExistsAndIsInstantiable(string $className): bool
    {
        return class_exists($className) && !(new ReflectionClass($className))->isAbstract();
    }
}