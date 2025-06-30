<?php

namespace Tourze\DoctrineEntityMarkdownBundle\MCP\Tool;

use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Tourze\MCPContracts\ToolInterface;

class GetDatabaseDictionary implements ToolInterface
{
    public function __construct(
        private readonly EntityService $entityService,
    ) {
    }

    public function getName(): string
    {
        return 'GetDatabaseDictionary';
    }

    public function getDescription(): string
    {
        return '返回完整的数据库字典，包含所有实体的表名、字段定义和关联关系';
    }

    public function getParameters(): \Traversable
    {
        return new \ArrayIterator([]);
    }

    public function execute(array $parameters = []): string
    {
        return $this->entityService->generateDatabaseMarkdown();
    }
}
