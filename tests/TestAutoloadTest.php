<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\TestAutoload;

/**
 * @codeCoverageIgnore
 */
#[CoversClass(TestAutoload::class)]
class TestAutoloadTest extends TestCase
{
    public function testAutoload(): void
    {
        $this->assertTrue(true);
    }
}