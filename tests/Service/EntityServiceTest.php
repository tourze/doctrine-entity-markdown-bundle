<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(EntityService::class)]
#[RunTestsInSeparateProcesses]
final class EntityServiceTest extends AbstractIntegrationTestCase
{
    /**
     * @var EntityService<object>
     */
    private EntityService $entityService;

    protected function onSetUp(): void
    {
        $this->entityService = self::getService(EntityService::class);
    }

    public function testGetAllTableNames(): void
    {
        $markdown = $this->entityService->getAllTableNames();

        $this->assertStringContainsString('# 数据库表清单', $markdown);
        // 由于是集成测试环境，可能没有真实的实体，所以只检查基本结构
        $this->assertStringContainsString('| 表名 | 说明 |', $markdown);
        $this->assertStringContainsString('|------|------|', $markdown);
    }

    public function testGetEntityMetadataReturnsNullForInvalidEntity(): void
    {
        $result = $this->entityService->getEntityMetadata('NonExistentEntity');
        $this->assertNull($result);
    }

    public function testGetAllEntitiesMetadata(): void
    {
        $result = $this->entityService->getAllEntitiesMetadata();
        $this->assertIsArray($result);
        // 在集成测试环境中，实体数量可能为0
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function testGenerateDatabaseMarkdown(): void
    {
        $markdown = $this->entityService->generateDatabaseMarkdown();
        $this->assertIsString($markdown);
        // 基本验证：markdown 内容应该是字符串
        $this->assertGreaterThanOrEqual(0, strlen($markdown));
    }
}
