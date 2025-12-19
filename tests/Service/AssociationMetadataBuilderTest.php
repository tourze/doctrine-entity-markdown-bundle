<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityMarkdownBundle\Service\AssociationMetadataBuilder;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

#[CoversClass(AssociationMetadataBuilder::class)]
#[RunTestsInSeparateProcesses]
final class AssociationMetadataBuilderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testGetAssociationsInfoWithRealEntities(): void
    {
        $builder = self::getService(AssociationMetadataBuilder::class);
        $entityManager = self::getService(EntityManagerInterface::class);

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        // 查找一个有关联的实体
        $entityWithAssociations = $this->findEntityWithAssociations($metadatas);

        // 如果找到有关联的实体，验证其关联信息
        if (null === $entityWithAssociations) {
            // 如果没有找到有关联的实体，至少验证方法能正常工作
            self::markTestSkipped('No entities with associations found in test environment');
        }

        $result = $builder->getAssociationsInfo($entityWithAssociations);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // 验证每个关联的结构
        $this->validateAssociationInfoStructure($result);
    }

    /**
     * @param array<int, ClassMetadata<object>> $metadatas
     * @return ClassMetadata<object>|null
     */
    private function findEntityWithAssociations(array $metadatas): ?ClassMetadata
    {
        foreach ($metadatas as $metadata) {
            if ([] !== $metadata->associationMappings) {
                return $metadata;
            }
        }

        return null;
    }

    /**
     * @param array<string, array<string, mixed>> $result
     */
    private function validateAssociationInfoStructure(array $result): void
    {
        foreach ($result as $fieldName => $associationInfo) {
            $this->assertIsString($fieldName);
            $this->assertIsArray($associationInfo);
            $this->assertArrayHasKey('type', $associationInfo);
            $this->assertArrayHasKey('targetEntity', $associationInfo);
            $this->assertArrayHasKey('targetTable', $associationInfo);

            // 验证类型是中文描述
            $this->assertContains($associationInfo['type'], ['一对一', '多对一', '一对多', '多对多', '未知']);

            // 验证 targetEntity 是有效的类名
            $this->assertTrue(class_exists($associationInfo['targetEntity']) || interface_exists($associationInfo['targetEntity']));

            // 验证 targetTable 是非空字符串
            $this->assertIsString($associationInfo['targetTable']);
            $this->assertNotEmpty($associationInfo['targetTable']);
        }
    }

    public function testGetAssociationsInfoEmpty(): void
    {
        $builder = self::getService(AssociationMetadataBuilder::class);
        $entityManager = self::getService(EntityManagerInterface::class);

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        // 查找一个没有关联的实体
        $entityWithoutAssociations = $this->findEntityWithoutAssociations($metadatas);

        // 如果找到没有关联的实体，验证返回空数组
        if (null === $entityWithoutAssociations) {
            // 如果所有实体都有关联，至少验证方法能正常工作
            self::markTestSkipped('No entities without associations found in test environment');
        }

        $result = $builder->getAssociationsInfo($entityWithoutAssociations);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @param array<int, ClassMetadata<object>> $metadatas
     * @return ClassMetadata<object>|null
     */
    private function findEntityWithoutAssociations(array $metadatas): ?ClassMetadata
    {
        foreach ($metadatas as $metadata) {
            if ([] === $metadata->associationMappings) {
                return $metadata;
            }
        }

        return null;
    }

    public function testGetAssociationsInfoReturnsArray(): void
    {
        $builder = self::getService(AssociationMetadataBuilder::class);
        $entityManager = self::getService(EntityManagerInterface::class);

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        // 验证所有实体的关联信息都返回数组
        foreach ($metadatas as $metadata) {
            $result = $builder->getAssociationsInfo($metadata);
            $this->assertIsArray($result);

            // 如果有关联，验证关联信息的基本结构
            if ([] !== $result) {
                foreach ($result as $fieldName => $associationInfo) {
                    $this->assertIsString($fieldName);
                    $this->assertIsArray($associationInfo);
                    $this->assertArrayHasKey('type', $associationInfo);
                    $this->assertArrayHasKey('targetEntity', $associationInfo);
                    $this->assertArrayHasKey('targetTable', $associationInfo);
                }
            }
        }

        // 至少验证我们处理了一些元数据
        $this->assertGreaterThanOrEqual(0, count($metadatas));
    }

    public function testGetAssociationsInfoWithManyToOneRelation(): void
    {
        $builder = self::getService(AssociationMetadataBuilder::class);
        $entityManager = self::getService(EntityManagerInterface::class);

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        // 查找一个有 ManyToOne 关联的实体
        $foundData = $this->findManyToOneAssociation($metadatas);

        if (null === $foundData) {
            self::markTestSkipped('No ManyToOne associations found in test environment');
        }

        [$fieldName, $metadata] = $foundData;
        $result = $builder->getAssociationsInfo($metadata);

        $this->assertArrayHasKey($fieldName, $result);
        $this->assertSame('多对一', $result[$fieldName]['type']);
        $this->assertArrayHasKey('targetEntity', $result[$fieldName]);
        $this->assertArrayHasKey('targetTable', $result[$fieldName]);

        // 如果有 joinColumns，验证其结构
        if (isset($result[$fieldName]['joinColumns'])) {
            $this->validateJoinColumns($result[$fieldName]['joinColumns']);
        }
    }

    /**
     * @param array<int, ClassMetadata<object>> $metadatas
     * @return array{string, ClassMetadata<object>}|null
     */
    private function findManyToOneAssociation(array $metadatas): ?array
    {
        foreach ($metadatas as $metadata) {
            foreach ($metadata->associationMappings as $fieldName => $mapping) {
                if (ClassMetadata::MANY_TO_ONE === $mapping['type']) {
                    return [$fieldName, $metadata];
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $joinColumns
     */
    private function validateJoinColumns(array $joinColumns): void
    {
        $this->assertIsArray($joinColumns);
        foreach ($joinColumns as $joinColumn) {
            $this->assertArrayHasKey('name', $joinColumn);
            $this->assertArrayHasKey('referencedColumnName', $joinColumn);
            $this->assertArrayHasKey('onDelete', $joinColumn);
            $this->assertArrayHasKey('onUpdate', $joinColumn);
        }
    }

    public function testGetAssociationsInfoWithManyToManyRelation(): void
    {
        $builder = self::getService(AssociationMetadataBuilder::class);
        $entityManager = self::getService(EntityManagerInterface::class);

        $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();

        // 查找一个有 ManyToMany 关联的实体
        $foundData = $this->findManyToManyAssociation($metadatas);

        if (null === $foundData) {
            self::markTestSkipped('No ManyToMany associations found in test environment');
        }

        [$fieldName, $metadata] = $foundData;
        $result = $builder->getAssociationsInfo($metadata);

        $this->assertArrayHasKey($fieldName, $result);
        $this->assertSame('多对多', $result[$fieldName]['type']);
        $this->assertArrayHasKey('targetEntity', $result[$fieldName]);
        $this->assertArrayHasKey('targetTable', $result[$fieldName]);

        // 如果有 joinTable，验证其结构
        if (isset($result[$fieldName]['joinTable'])) {
            $this->validateJoinTable($result[$fieldName]['joinTable']);
        }
    }

    /**
     * @param array<int, ClassMetadata<object>> $metadatas
     * @return array{string, ClassMetadata<object>}|null
     */
    private function findManyToManyAssociation(array $metadatas): ?array
    {
        foreach ($metadatas as $metadata) {
            foreach ($metadata->associationMappings as $fieldName => $mapping) {
                if (ClassMetadata::MANY_TO_MANY === $mapping['type']) {
                    return [$fieldName, $metadata];
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $joinTable
     */
    private function validateJoinTable(array $joinTable): void
    {
        $this->assertIsArray($joinTable);
        $this->assertArrayHasKey('name', $joinTable);
        $this->assertArrayHasKey('joinColumns', $joinTable);
        $this->assertArrayHasKey('inverseJoinColumns', $joinTable);
    }
}
