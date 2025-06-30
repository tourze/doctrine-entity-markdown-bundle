<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\NamingStrategy;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Tourze\DoctrineEntityMarkdownBundle\Tests\Fixtures\Entity\TestUser;
use Tourze\DoctrineEntityMarkdownBundle\Tests\Fixtures\Entity\TestOrder;

class EntityServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private NamingStrategy $namingStrategy;
    private ClassMetadataFactory $metadataFactory;
    private EntityService $entityService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->namingStrategy = $this->createMock(NamingStrategy::class);
        $this->metadataFactory = $this->createMock(ClassMetadataFactory::class);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getNamingStrategy')->willReturn($this->namingStrategy);

        $this->entityManager->method('getConfiguration')->willReturn($configuration);
        $this->entityManager->method('getMetadataFactory')->willReturn($this->metadataFactory);

        $this->entityService = new EntityService($this->entityManager);
    }

    public function testGetAllTableNames(): void
    {
        // 创建模拟的元数据
        $metadata1 = $this->createPartialMock(ClassMetadata::class, ['getTableName']);
        $metadata1->method('getTableName')->willReturn('user_table');
        $metadata1->table['options']['comment'] = '用户表';

        $metadata2 = $this->createPartialMock(ClassMetadata::class, ['getTableName']);
        $metadata2->method('getTableName')->willReturn('order_table');
        $metadata2->table['options']['comment'] = '订单表';

        $this->metadataFactory->method('getAllMetadata')->willReturn([$metadata1, $metadata2]);

        $markdown = $this->entityService->getAllTableNames();

        $this->assertStringContainsString('# 数据库表清单', $markdown);
        $this->assertStringContainsString('user_table', $markdown);
        $this->assertStringContainsString('order_table', $markdown);
        $this->assertStringContainsString('用户表', $markdown);
        $this->assertStringContainsString('订单表', $markdown);
    }

    public function testGetEntityMetadata(): void
    {
        $metadata = $this->createPartialMock(ClassMetadata::class, ['getTableName']);
        $metadata->method('getTableName')->willReturn('user_table');
        $metadata->name = TestUser::class;
        $metadata->table['options']['comment'] = '用户表';
        $metadata->identifier = ['id'];
        $idMapping = new FieldMapping('integer', 'id', 'id');
        $idMapping->options = ['comment' => '用户ID'];
        
        $nameMapping = new FieldMapping('string', 'name', 'name');
        $nameMapping->length = 255;
        $nameMapping->nullable = false;
        $nameMapping->options = ['comment' => '用户名称'];
        
        $metadata->fieldMappings = [
            'id' => $idMapping,
            'name' => $nameMapping,
        ];
        $metadata->associationMappings = [];

        $this->entityManager->method('getClassMetadata')->willReturn($metadata);
        $this->namingStrategy->method('propertyToColumnName')->willReturnCallback(function ($property) {
            return $property; // 简单返回属性名作为列名
        });

        $result = $this->entityService->getEntityMetadata(TestUser::class);
        $this->assertEquals('user_table', $result['tableName']);
        $this->assertEquals('用户表', $result['comment']);
        $this->assertArrayHasKey('fields', $result);
        $this->assertArrayHasKey('associations', $result);
    }

    public function testGetEntityMetadataReturnsNullForInvalidEntity(): void
    {
        $this->entityManager->method('getClassMetadata')
            ->willThrowException(new \Exception('Entity not found'));

        $result = $this->entityService->getEntityMetadata('NonExistentEntity');
        $this->assertNull($result);
    }

    public function testGetAllEntitiesMetadata(): void
    {
        // 创建模拟的元数据
        $metadata1 = $this->createPartialMock(ClassMetadata::class, ['getTableName', 'getName']);
        $metadata1->method('getName')->willReturn(TestUser::class);
        $metadata1->method('getTableName')->willReturn('user_table');
        $metadata1->name = TestUser::class;  // 设置名称属性
        $metadata1->table['options']['comment'] = '用户表';
        $metadata1->identifier = ['id'];
        $idMapping1 = new FieldMapping('integer', 'id', 'id');
        $idMapping1->options = ['comment' => '用户ID'];
        
        $metadata1->fieldMappings = [
            'id' => $idMapping1,
        ];
        $metadata1->associationMappings = [];

        $metadata2 = $this->createPartialMock(ClassMetadata::class, ['getTableName', 'getName']);
        $metadata2->method('getName')->willReturn(TestOrder::class);
        $metadata2->method('getTableName')->willReturn('order_table');
        $metadata2->name = TestOrder::class;  // 设置名称属性
        $metadata2->table['options']['comment'] = '订单表';
        $metadata2->identifier = ['id'];
        $idMapping2 = new FieldMapping('integer', 'id', 'id');
        $idMapping2->options = ['comment' => '订单ID'];
        
        $metadata2->fieldMappings = [
            'id' => $idMapping2,
        ];
        $metadata2->associationMappings = [];

        $this->metadataFactory->method('getAllMetadata')->willReturn([$metadata1, $metadata2]);
        $this->namingStrategy->method('propertyToColumnName')->willReturnCallback(function ($property) {
            return $property; // 简单返回属性名作为列名
        });

        $result = $this->entityService->getAllEntitiesMetadata();
        $this->assertCount(2, $result);
        $this->assertArrayHasKey(TestUser::class, $result);
        $this->assertArrayHasKey(TestOrder::class, $result);
    }

    public function testGetRelationType(): void
    {
        $reflectionMethod = new \ReflectionMethod(EntityService::class, 'getRelationType');
        $reflectionMethod->setAccessible(true);

        $this->assertEquals('一对一', $reflectionMethod->invoke($this->entityService, ClassMetadata::ONE_TO_ONE));
        $this->assertEquals('多对一', $reflectionMethod->invoke($this->entityService, ClassMetadata::MANY_TO_ONE));
        $this->assertEquals('一对多', $reflectionMethod->invoke($this->entityService, ClassMetadata::ONE_TO_MANY));
        $this->assertEquals('多对多', $reflectionMethod->invoke($this->entityService, ClassMetadata::MANY_TO_MANY));
        $this->assertEquals('未知', $reflectionMethod->invoke($this->entityService, 999));
    }

    public function testGetColumnType(): void
    {
        $reflectionMethod = new \ReflectionMethod(EntityService::class, 'getColumnType');
        $reflectionMethod->setAccessible(true);

        $this->assertEquals('varchar', $reflectionMethod->invoke($this->entityService, ['type' => 'string']));
        $this->assertEquals('int', $reflectionMethod->invoke($this->entityService, ['type' => 'integer']));
        $this->assertEquals('json', $reflectionMethod->invoke($this->entityService, ['type' => 'json']));
        $this->assertEquals('custom_type', $reflectionMethod->invoke($this->entityService, ['type' => 'custom_type']));
    }

    public function testGenerateDatabaseMarkdown(): void
    {
        // 设置完整的元数据模拟
        $metadata1 = $this->createPartialMock(ClassMetadata::class, ['getTableName', 'getName']);
        $metadata1->method('getName')->willReturn(TestUser::class);
        $metadata1->method('getTableName')->willReturn('user_table');
        $metadata1->name = TestUser::class;
        $metadata1->table['options']['comment'] = '用户表';
        $metadata1->identifier = ['id'];

        $idMapping = new FieldMapping('integer', 'id', 'id');
        $idMapping->options = ['comment' => '用户ID'];

        $nameMapping = new FieldMapping('string', 'name', 'name');
        $nameMapping->length = 255;
        $nameMapping->nullable = false;
        $nameMapping->options = ['comment' => '用户名称'];

        $metadata1->fieldMappings = [
            'id' => $idMapping,
            'name' => $nameMapping,
        ];

        // 设置关联关系数据（简化版本，不测试复杂关联）
        $metadata1->associationMappings = [];

        // 模拟目标实体的元数据
        $targetMetadata = $this->createPartialMock(ClassMetadata::class, ['getTableName']);
        $targetMetadata->method('getTableName')->willReturn('order_table');

        $this->metadataFactory->method('getAllMetadata')->willReturn([$metadata1]);
        $this->entityManager->method('getClassMetadata')
            ->willReturnMap([
                [TestUser::class, $metadata1],
                [TestOrder::class, $targetMetadata],
            ]);

        $this->namingStrategy->method('propertyToColumnName')->willReturnCallback(function ($property) {
            return $property;
        });

        $markdown = $this->entityService->generateDatabaseMarkdown();

        // 验证包含关键信息
        $this->assertStringContainsString('user_table', $markdown);
        $this->assertStringContainsString('用户表', $markdown);
        $this->assertStringContainsString('用户ID', $markdown);
        $this->assertStringContainsString('用户名称', $markdown);
    }
}
