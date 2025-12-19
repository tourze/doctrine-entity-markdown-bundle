<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class DoctrineEntityMarkdownExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
