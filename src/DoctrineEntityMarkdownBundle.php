<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class DoctrineEntityMarkdownBundle extends Bundle implements BundleDependencyInterface
{
    public function boot(): void
    {
        // 自动加载依赖类
        require_once __DIR__ . '/Bootstrap.php';
        require_once __DIR__ . '/BootstrapPHPUnit.php';

        parent::boot();
    }

    /**
     * @return array<string, array<string, mixed>> Bundle dependencies
     */
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
        ];
    }
}
