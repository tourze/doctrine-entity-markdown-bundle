<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\MCP\Tool;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityMarkdownBundle\MCP\Tool\GetDatabaseDictionary;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(GetDatabaseDictionary::class)]
#[RunTestsInSeparateProcesses]
final class GetDatabaseDictionaryTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testGetName(): void
    {
        $tool = self::getService(GetDatabaseDictionary::class);
        $this->assertEquals('GetDatabaseDictionary', $tool->getName());
    }

    public function testGetDescription(): void
    {
        $tool = self::getService(GetDatabaseDictionary::class);
        $description = $tool->getDescription();
        $this->assertNotEmpty($description);
        $this->assertStringContainsString('数据库', $description);
        $this->assertStringContainsString('字典', $description);
    }

    public function testGetParameters(): void
    {
        $tool = self::getService(GetDatabaseDictionary::class);
        $parameters = $tool->getParameters();
        $this->assertInstanceOf(\Traversable::class, $parameters);
        $this->assertCount(0, iterator_to_array($parameters));
    }

    public function testExecute(): void
    {
        $tool = self::getService(GetDatabaseDictionary::class);
        $result = $tool->execute();
        $this->assertIsString($result);
    }
}
