<?php

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
    private GetDatabaseDictionary $tool;

    protected function onSetUp(): void
    {
        $this->tool = self::getService(GetDatabaseDictionary::class);
    }

    public function testGetName(): void
    {
        $this->assertEquals('GetDatabaseDictionary', $this->tool->getName());
    }

    public function testGetDescription(): void
    {
        $this->assertEquals(
            '返回完整的数据库字典，包含所有实体的表名、字段定义和关联关系',
            $this->tool->getDescription()
        );
    }

    public function testGetParameters(): void
    {
        $parameters = $this->tool->getParameters();
        $this->assertInstanceOf(\Traversable::class, $parameters);
        $this->assertCount(0, iterator_to_array($parameters));
    }

    public function testExecute(): void
    {
        $result = $this->tool->execute();
        $this->assertIsString($result);
        $this->assertStringContainsString('## ', $result);
        $this->assertStringContainsString('### 字段', $result);
    }

    public function testExecuteWithParameters(): void
    {
        $result = $this->tool->execute(['ignored' => 'parameter']);
        $this->assertIsString($result);
        $this->assertStringContainsString('## ', $result);
        $this->assertStringContainsString('### 字段', $result);
    }
}
