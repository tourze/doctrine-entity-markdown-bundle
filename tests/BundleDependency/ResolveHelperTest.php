<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\BundleDependency;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\BundleDependency\ResolveHelper;

/**
 * @codeCoverageIgnore
 */
#[CoversClass(ResolveHelper::class)]
class ResolveHelperTest extends TestCase
{
    public function testResolveBundleDependencies(): void
    {
        $bundles = [
            'TestBundle' => ['all' => true],
        ];

        $result = ResolveHelper::resolveBundleDependencies($bundles);

        $this->assertIsArray($result);
    }
}