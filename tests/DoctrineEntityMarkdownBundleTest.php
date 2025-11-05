<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityMarkdownBundle\DoctrineEntityMarkdownBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineEntityMarkdownBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrineEntityMarkdownBundleTest extends AbstractBundleTestCase
{
}
