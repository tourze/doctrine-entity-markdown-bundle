<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\MCP\Tool;



use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrineEntityMarkdownBundle\MCP\Tool\GetDatabaseDictionary;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityServiceInterface;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(GetDatabaseDictionary::class)]
final class GetDatabaseDictionaryTest extends TestCase
{
    private GetDatabaseDictionary $tool;
    /** @var \PHPUnit\Framework\MockObject\MockObject&EntityServiceInterface */
    private $entityService;

    protected function setUp(): void
    {
        $this->entityService = $this->createMock(EntityServiceInterface::class);
        $this->tool = new GetDatabaseDictionary($this->entityService);
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
        $mockMarkdown = "## 测试表\n### 字段\n| 字段名 | 类型 | 说明 |\n|--------|------|------|\n| id | integer | 主键 |\n";
        $this->entityService->method('generateDatabaseMarkdown')->willReturn($mockMarkdown);

        $result = $this->tool->execute();
        $this->assertIsString($result);
        $this->assertStringContainsString('## ', $result);
        $this->assertStringContainsString('### 字段', $result);
    }

    public function testExecuteWithParameters(): void
    {
        $mockMarkdown = "## 测试表\n### 字段\n| 字段名 | 类型 | 说明 |\n|--------|------|------|\n| id | integer | 主键 |\n";
        $this->entityService->method('generateDatabaseMarkdown')->willReturn($mockMarkdown);

        $result = $this->tool->execute(['ignored' => 'parameter']);
        $this->assertIsString($result);
        $this->assertStringContainsString('## ', $result);
        $this->assertStringContainsString('### 字段', $result);
    }
}
