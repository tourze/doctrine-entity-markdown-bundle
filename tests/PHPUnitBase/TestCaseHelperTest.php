<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\PHPUnitBase;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitBase\TestCaseHelper;

/**
 * @codeCoverageIgnore
 */
#[CoversClass(TestCaseHelper::class)]
class TestCaseHelperTest extends TestCase
{
    public function testExtractCoverClass(): void
    {
        $reflection = new \ReflectionClass($this);
        $coverClass = TestCaseHelper::extractCoverClass($reflection);

        $this->assertSame(TestCaseHelper::class, $coverClass);
    }

    public function testIsTestClass(): void
    {
        $this->assertTrue(TestCaseHelper::isTestClass(self::class));
        $this->assertTrue(TestCaseHelper::isTestClass('SomeTest'));
        $this->assertTrue(TestCaseHelper::isTestClass('SomeTestCase'));
        $this->assertTrue(TestCaseHelper::isTestClass('App\\Tests\\SomeClass'));
        $this->assertFalse(TestCaseHelper::isTestClass('App\\Service\\SomeService'));
    }
}
