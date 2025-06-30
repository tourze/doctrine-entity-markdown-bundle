<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\MCP\Tool;

use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\MCP\Tool\GetDatabaseDictionary;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;

class GetDatabaseDictionaryTest extends TestCase
{
    private EntityService $entityService;
    private GetDatabaseDictionary $tool;

    protected function setUp(): void
    {
        $this->entityService = $this->createMock(EntityService::class);
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
        $expectedMarkdown = '# 数据库字典\n\n## user_table\n...';
        $this->entityService->expects($this->once())
            ->method('generateDatabaseMarkdown')
            ->willReturn($expectedMarkdown);

        $result = $this->tool->execute();
        $this->assertEquals($expectedMarkdown, $result);
    }

    public function testExecuteWithParameters(): void
    {
        $expectedMarkdown = '# 数据库字典\n\n## user_table\n...';
        $this->entityService->expects($this->once())
            ->method('generateDatabaseMarkdown')
            ->willReturn($expectedMarkdown);

        $result = $this->tool->execute(['ignored' => 'parameter']);
        $this->assertEquals($expectedMarkdown, $result);
    }
}