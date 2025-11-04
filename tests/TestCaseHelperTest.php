<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Tourze\DoctrineEntityMarkdownBundle\Tests\SomeOtherClassTest;
use Tourze\PHPUnitBase\TestCaseHelper;

#[CoversClass(TestCaseHelper::class)]
class TestCaseHelperTest extends TestCase
{
    public function testExtractCoverClassFromAttribute(): void
    {
        $reflection = new ReflectionClass($this);
        $result = TestCaseHelper::extractCoverClass($reflection);
        $this->assertSame(TestCaseHelper::class, $result);
    }

    
    public function testIsTestClass(): void
    {
        $this->assertTrue(TestCaseHelper::isTestClass('MyTest'));
        $this->assertTrue(TestCaseHelper::isTestClass('MyTestCase'));
        $this->assertTrue(TestCaseHelper::isTestClass('MyApp\\Tests\\Something'));
        $this->assertFalse(TestCaseHelper::isTestClass('MyClass'));
    }
}