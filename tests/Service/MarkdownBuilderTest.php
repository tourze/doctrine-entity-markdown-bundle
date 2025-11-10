<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityMarkdownBundle\Service\MarkdownBuilder;

/**
 * @codeCoverageIgnore
 */
#[CoversClass(MarkdownBuilder::class)]
class MarkdownBuilderTest extends TestCase
{
    private MarkdownBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MarkdownBuilder();
    }

    public function testGetFieldsMarkdownHeader(): void
    {
        $header = $this->builder->getFieldsMarkdownHeader();

        $this->assertStringContainsString('### 字段', $header);
        $this->assertStringContainsString('| 名称 | 类型 | 长度 | 允许空 | 默认值 | 说明 |', $header);
    }

    public function testGenerateSingleFieldMarkdownRow(): void
    {
        $field = [
            'columnName' => 'id',
            'type' => 'int',
            'length' => null,
            'nullable' => false,
            'default' => null,
            'comment' => '主键',
        ];

        $row = $this->builder->generateSingleFieldMarkdownRow($field);

        $this->assertStringContainsString('| id |', $row);
        $this->assertStringContainsString('| int |', $row);
        $this->assertStringContainsString('| N |', $row);
        $this->assertStringContainsString('| 主键 |', $row);
    }

    public function testGenerateFieldsMarkdown(): void
    {
        $fields = [
            [
                'columnName' => 'id',
                'type' => 'int',
                'length' => null,
                'nullable' => false,
                'default' => null,
                'comment' => '主键',
            ],
            [
                'columnName' => 'name',
                'type' => 'varchar',
                'length' => 255,
                'nullable' => true,
                'default' => null,
                'comment' => '名称',
            ],
        ];

        $markdown = $this->builder->generateFieldsMarkdown($fields);

        $this->assertStringContainsString('### 字段', $markdown);
        $this->assertStringContainsString('| id |', $markdown);
        $this->assertStringContainsString('| name |', $markdown);
    }

    public function testGenerateAssociationsMarkdown(): void
    {
        $associations = [
            'user' => [
                'type' => '多对一',
                'targetTable' => 'users',
                'joinColumns' => [
                    [
                        'name' => 'user_id',
                        'referencedColumnName' => 'id',
                    ],
                ],
            ],
        ];

        $markdown = $this->builder->generateAssociationsMarkdown($associations);

        $this->assertStringContainsString('### 关系', $markdown);
        $this->assertStringContainsString('多对一', $markdown);
        $this->assertStringContainsString('users', $markdown);
    }

    public function testGenerateAssociationsMarkdownEmpty(): void
    {
        $markdown = $this->builder->generateAssociationsMarkdown([]);

        $this->assertSame('', $markdown);
    }

    public function testExtractFieldDisplayData(): void
    {
        $field = [
            'columnName' => 'id',
            'type' => 'int',
            'length' => 11,
            'nullable' => false,
            'default' => 0,
            'comment' => '主键ID',
        ];

        $displayData = $this->builder->extractFieldDisplayData($field);

        $this->assertArrayHasKey('columnName', $displayData);
        $this->assertArrayHasKey('type', $displayData);
        $this->assertArrayHasKey('length', $displayData);
        $this->assertArrayHasKey('nullable', $displayData);
        $this->assertArrayHasKey('default', $displayData);
        $this->assertArrayHasKey('comment', $displayData);

        $this->assertSame('id', $displayData['columnName']);
        $this->assertSame('int', $displayData['type']);
        $this->assertSame('11', $displayData['length']);
        $this->assertSame('N', $displayData['nullable']);
        $this->assertSame('0', $displayData['default']);
        $this->assertSame('主键ID', $displayData['comment']);
    }
}
