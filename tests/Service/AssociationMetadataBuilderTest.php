<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\Service\AssociationMetadataBuilder;

/**
 * @codeCoverageIgnore
 */
#[CoversClass(AssociationMetadataBuilder::class)]
class AssociationMetadataBuilderTest extends TestCase
{
    private AssociationMetadataBuilder $builder;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->builder = new AssociationMetadataBuilder($this->entityManager);
    }

    public function testGetAssociationsInfoWithManyToOne(): void
    {
        // 创建目标实体的 ClassMetadata
        $targetMetadata = $this->createMock(ClassMetadata::class);
        $targetMetadata->method('getTableName')->willReturn('test_entity');

        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($targetMetadata);

        // 创建当前实体的 ClassMetadata (使用 stdClass 作为存在的类)
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->associationMappings = [
            'related' => [
                'type' => ClassMetadata::MANY_TO_ONE,
                'targetEntity' => \stdClass::class,
                'joinColumns' => [
                    [
                        'name' => 'related_id',
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE',
                        'onUpdate' => null,
                    ],
                ],
            ],
        ];

        $result = $this->builder->getAssociationsInfo($metadata);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('related', $result);
        $this->assertSame('多对一', $result['related']['type']);
        $this->assertSame('test_entity', $result['related']['targetTable']);
        $this->assertArrayHasKey('joinColumns', $result['related']);
        $this->assertSame('related_id', $result['related']['joinColumns'][0]['name']);
        $this->assertSame('CASCADE', $result['related']['joinColumns'][0]['onDelete']);
    }

    public function testGetAssociationsInfoWithManyToMany(): void
    {
        // 创建目标实体的 ClassMetadata
        $targetMetadata = $this->createMock(ClassMetadata::class);
        $targetMetadata->method('getTableName')->willReturn('test_target');

        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($targetMetadata);

        // 创建当前实体的 ClassMetadata (使用 stdClass 作为存在的类)
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->associationMappings = [
            'items' => [
                'type' => ClassMetadata::MANY_TO_MANY,
                'targetEntity' => \stdClass::class,
                'joinTable' => [
                    'name' => 'test_join_table',
                    'joinColumns' => [
                        [
                            'name' => 'source_id',
                            'referencedColumnName' => 'id',
                        ],
                    ],
                    'inverseJoinColumns' => [
                        [
                            'name' => 'target_id',
                            'referencedColumnName' => 'id',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->builder->getAssociationsInfo($metadata);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertSame('多对多', $result['items']['type']);
        $this->assertArrayHasKey('joinTable', $result['items']);
        $this->assertSame('test_join_table', $result['items']['joinTable']['name']);
        $this->assertCount(1, $result['items']['joinTable']['joinColumns']);
        $this->assertCount(1, $result['items']['joinTable']['inverseJoinColumns']);
    }

    public function testGetAssociationsInfoEmpty(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->associationMappings = [];

        $result = $this->builder->getAssociationsInfo($metadata);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetAssociationsInfoWithInvalidMapping(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->associationMappings = [
            'invalid' => 'not_an_array',
        ];

        $result = $this->builder->getAssociationsInfo($metadata);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
