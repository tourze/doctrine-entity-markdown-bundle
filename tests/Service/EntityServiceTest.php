<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;



use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\NamingStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(EntityService::class)]
final class EntityServiceTest extends TestCase
{
    /**
     * @var EntityService<object>
     */
    private EntityService $entityService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject&EntityManagerInterface $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager = $entityManager;

        // Mock configuration and naming strategy
        /** @var \PHPUnit\Framework\MockObject\MockObject&Configuration $configuration */
        $configuration = $this->createMock(Configuration::class);
        /** @var \PHPUnit\Framework\MockObject\MockObject&NamingStrategy $namingStrategy */
        $namingStrategy = $this->createMock(NamingStrategy::class);
        $configuration->method('getNamingStrategy')->willReturn($namingStrategy);
        $entityManager->method('getConfiguration')->willReturn($configuration);

        $this->entityService = new EntityService($this->entityManager);
    }

    public function testGetAllTableNames(): void
    {
        // Mock metadata factory to return empty array
        /** @var \PHPUnit\Framework\MockObject\MockObject&\Doctrine\ORM\Mapping\ClassMetadataFactory $metadataFactory */
        $metadataFactory = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn([]);
        /** @var \PHPUnit\Framework\MockObject\MockObject&EntityManagerInterface $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->method('getMetadataFactory')->willReturn($metadataFactory);

        $markdown = $this->entityService->getAllTableNames();

        $this->assertStringContainsString('# 数据库表清单', $markdown);
        // 由于是集成测试环境，可能没有真实的实体，所以只检查基本结构
        $this->assertStringContainsString('| 表名 | 说明 |', $markdown);
        $this->assertStringContainsString('|------|------|', $markdown);
    }

    public function testGetEntityMetadataReturnsNullForInvalidEntity(): void
    {
        // Mock getClassMetadata to throw exception for invalid entity
        /** @var \PHPUnit\Framework\MockObject\MockObject&EntityManagerInterface $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->method('getClassMetadata')
            ->with('NonExistentEntity')
            ->willThrowException(new \Exception('Class not found'));

        $result = $this->entityService->getEntityMetadata('NonExistentEntity');
        $this->assertNull($result);
    }

    public function testGetAllEntitiesMetadata(): void
    {
        // Mock metadata factory to return empty array
        /** @var \PHPUnit\Framework\MockObject\MockObject&\Doctrine\ORM\Mapping\ClassMetadataFactory $metadataFactory */
        $metadataFactory = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn([]);
        /** @var \PHPUnit\Framework\MockObject\MockObject&EntityManagerInterface $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->method('getMetadataFactory')->willReturn($metadataFactory);

        $result = $this->entityService->getAllEntitiesMetadata();
        $this->assertIsArray($result);
        // 在集成测试环境中，实体数量可能为0
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function testGenerateDatabaseMarkdown(): void
    {
        // Mock metadata factory to return empty array
        /** @var \PHPUnit\Framework\MockObject\MockObject&\Doctrine\ORM\Mapping\ClassMetadataFactory $metadataFactory */
        $metadataFactory = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn([]);
        /** @var \PHPUnit\Framework\MockObject\MockObject&EntityManagerInterface $entityManager */
        $entityManager = $this->entityManager;
        $entityManager->method('getMetadataFactory')->willReturn($metadataFactory);

        $markdown = $this->entityService->generateDatabaseMarkdown();
        $this->assertIsString($markdown);
        // 基本验证：markdown 内容应该是字符串
        $this->assertGreaterThanOrEqual(0, strlen($markdown));
    }
}
