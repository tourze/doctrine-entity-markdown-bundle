<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\Service\FieldMetadataBuilder;

/**
 * @codeCoverageIgnore
 */
#[CoversClass(FieldMetadataBuilder::class)]
class FieldMetadataBuilderTest extends TestCase
{
    private FieldMetadataBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new FieldMetadataBuilder(new UnderscoreNamingStrategy());
    }

    public function testGetFieldsInfo(): void
    {
        // 创建一个模拟的 ClassMetadata
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = 'TestEntity';
        $metadata->identifier = ['id'];
        $metadata->fieldMappings = [
            'id' => [
                'fieldName' => 'id',
                'type' => 'integer',
                'columnName' => 'id',
                'nullable' => false,
                'options' => ['comment' => '主键ID'],
            ],
            'name' => [
                'fieldName' => 'name',
                'type' => 'string',
                'columnName' => 'name',
                'length' => 255,
                'nullable' => true,
                'options' => ['comment' => '名称'],
            ],
        ];

        $result = $this->builder->getFieldsInfo($metadata);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);

        // 验证主键字段
        $this->assertTrue($result['id']['isPrimaryKey']);
        $this->assertSame('id', $result['id']['columnName']);
        $this->assertSame('int', $result['id']['type']);

        // 验证普通字段
        $this->assertFalse($result['name']['isPrimaryKey']);
        $this->assertSame('name', $result['name']['columnName']);
        $this->assertSame('varchar', $result['name']['type']);
        $this->assertSame(255, $result['name']['length']);
        $this->assertTrue($result['name']['nullable']);
    }

    public function testGetTypeMapping(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = 'TestEntity';
        $metadata->identifier = [];
        $metadata->fieldMappings = [
            'stringField' => [
                'type' => 'string',
                'options' => [],
            ],
            'textField' => [
                'type' => 'text',
                'options' => [],
            ],
            'intField' => [
                'type' => 'integer',
                'options' => [],
            ],
            'boolField' => [
                'type' => 'boolean',
                'options' => [],
            ],
            'jsonField' => [
                'type' => 'json',
                'options' => [],
            ],
        ];

        $result = $this->builder->getFieldsInfo($metadata);

        $this->assertSame('varchar', $result['stringField']['type']);
        $this->assertSame('text', $result['textField']['type']);
        $this->assertSame('int', $result['intField']['type']);
        $this->assertSame('tinyint', $result['boolField']['type']);
        $this->assertSame('json', $result['jsonField']['type']);
    }
}
