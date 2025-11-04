<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests;



use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrineEntityMarkdownBundle\DoctrineEntityMarkdownBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineEntityMarkdownBundle::class)]
final class DoctrineEntityMarkdownBundleTest extends AbstractBundleTestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new DoctrineEntityMarkdownBundle();
        $this->assertInstanceOf(DoctrineEntityMarkdownBundle::class, $bundle);
    }
}
