<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\DoctrineEntityMarkdownBundle\Service\FieldMetadataBuilder;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

#[CoversClass(FieldMetadataBuilder::class)]
#[RunTestsInSeparateProcesses]
final class FieldMetadataBuilderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    public function testGetFieldsInfo(): void
    {
        $builder = self::getService(FieldMetadataBuilder::class);

        // 创建一个真实的 ClassMetadata 对象用于测试（使用 stdClass 作为占位符）
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->setPrimaryTable(['name' => 'test_entity']);

        // 直接设置 fieldMappings 为数组（兼容 FieldMetadataBuilder 的实现）
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
        $metadata->identifier = ['id'];

        $result = $builder->getFieldsInfo($metadata);

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
        $builder = self::getService(FieldMetadataBuilder::class);

        // 创建一个真实的 ClassMetadata 对象（使用 stdClass 作为占位符）
        $metadata = new ClassMetadata(\stdClass::class);
        $metadata->setPrimaryTable(['name' => 'test_entity']);

        // 直接设置 fieldMappings 为数组（兼容 FieldMetadataBuilder 的实现）
        $metadata->fieldMappings = [
            'stringField' => [
                'fieldName' => 'stringField',
                'type' => 'string',
                'options' => [],
            ],
            'textField' => [
                'fieldName' => 'textField',
                'type' => 'text',
                'options' => [],
            ],
            'intField' => [
                'fieldName' => 'intField',
                'type' => 'integer',
                'options' => [],
            ],
            'boolField' => [
                'fieldName' => 'boolField',
                'type' => 'boolean',
                'options' => [],
            ],
            'jsonField' => [
                'fieldName' => 'jsonField',
                'type' => 'json',
                'options' => [],
            ],
        ];

        $result = $builder->getFieldsInfo($metadata);

        $this->assertSame('varchar', $result['stringField']['type']);
        $this->assertSame('text', $result['textField']['type']);
        $this->assertSame('int', $result['intField']['type']);
        $this->assertSame('tinyint', $result['boolField']['type']);
        $this->assertSame('json', $result['jsonField']['type']);
    }
}
