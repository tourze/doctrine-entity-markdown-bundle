<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class DoctrineEntityMarkdownBundle extends Bundle implements BundleDependencyInterface
{
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
