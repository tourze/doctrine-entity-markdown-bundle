<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\NamingStrategy;

/**
 * @template TEntity of object
 */
final readonly class EntityService implements EntityServiceInterface
{
    private NamingStrategy $namingStrategy;
    private MarkdownBuilder $markdownBuilder;
    private FieldMetadataBuilder $fieldBuilder;
    private AssociationMetadataBuilder $associationBuilder;

    public function __construct(
        private EntityManagerInterface $entityManager,
        MarkdownBuilder $markdownBuilder = null,
        FieldMetadataBuilder $fieldBuilder = null,
        AssociationMetadataBuilder $associationBuilder = null,
    ) {
        $this->namingStrategy = $this->entityManager->getConfiguration()->getNamingStrategy();
        $this->markdownBuilder = $markdownBuilder ?? new MarkdownBuilder();
        $this->fieldBuilder = $fieldBuilder ?? new FieldMetadataBuilder($this->namingStrategy);
        $this->associationBuilder = $associationBuilder ?? new AssociationMetadataBuilder($this->entityManager);
    }

    /**
     * 获取所有实体的元数据
     *
     * @return array<class-string<object>, array{
     *     tableName: string,
     *     comment: string,
     *     fields: array<string, array<string, mixed>>,
     *     associations: array<string, array<string, mixed>>
     * }> 返回实体信息数组
     */
    public function getAllEntitiesMetadata(): array
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $result = [];

        /** @var ClassMetadata<object> $metadata */
        foreach ($metadatas as $metadata) {
            $result[$metadata->getName()] = $this->buildEntityMetadata($metadata);
        }

        return $result;
    }

    /**
     * 构建单个实体元数据
     *
     * @param ClassMetadata<object> $metadata
     * @return array{
     *     tableName: string,
     *     comment: string,
     *     fields: array<string, array<string, mixed>>,
     *     associations: array<string, array<string, mixed>>
     * }
     */
    private function buildEntityMetadata(ClassMetadata $metadata): array
    {
        return [
            'tableName' => $metadata->getTableName(),
            'comment' => $this->extractEntityComment($metadata),
            'fields' => $this->fieldBuilder->getFieldsInfo($metadata),
            'associations' => $this->associationBuilder->getAssociationsInfo($metadata),
        ];
    }

    /**
     * 提取实体注释
     *
     * @param ClassMetadata<object> $metadata
     */
    private function extractEntityComment(ClassMetadata $metadata): string
    {
        $tableOptions = $metadata->table['options'] ?? [];
        $comment = is_array($tableOptions) ? ($tableOptions['comment'] ?? '') : '';

        return is_string($comment) ? $comment : '';
    }

    /**
     * 获取单个实体的元数据
     *
     * @return array{
     *     tableName: string,
     *     comment: string,
     *     fields: array<string, array<string, mixed>>,
     *     associations: array<string, array<string, mixed>>
     * }|null
     */
    public function getEntityMetadata(string $entityClass): ?array
    {
        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);

            return $this->buildEntityMetadata($metadata);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 获取所有表名和注释的markdown格式文本
     */
    public function getAllTableNames(): string
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();

        $tableRows = array_map(fn ($metadata) => $this->buildTableRow($metadata), $metadatas);

        return "# 数据库表清单\n\n" .
               "| 表名 | 说明 |\n" .
               "|------|------|\n" .
               implode('', $tableRows);
    }

    /**
     * 构建表行
     *
     * @param ClassMetadata<object> $metadata
     */
    private function buildTableRow(ClassMetadata $metadata): string
    {
        $tableName = $metadata->getTableName();
        $comment = $this->extractEntityComment($metadata);

        return "| {$tableName} | {$comment} |\n";
    }

    /**
     * 获取指定表的字段和注释的markdown格式文本
     */
    public function getTableFields(string $tableName): string
    {
        $metadata = $this->findMetadataByTableName($tableName);

        return null === $metadata
            ? "# {$tableName}\n\n表不存在"
            : $this->buildTableFieldsMarkdown($tableName, $metadata);
    }

    /**
     * 构建表字段 Markdown
     *
     * @param ClassMetadata<object> $metadata
     */
    private function buildTableFieldsMarkdown(string $tableName, ClassMetadata $metadata): string
    {
        return "# {$tableName}\n\n" .
               "| 字段名 | 类型 | 说明 |\n" .
               "|--------|------|------|\n" .
               $this->buildFieldsMarkdown($metadata);
    }

    /**
     * 根据表名查找元数据
     *
     * @return ClassMetadata<object>|null
     */
    private function findMetadataByTableName(string $tableName): ?ClassMetadata
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();

        foreach ($metadatas as $metadata) {
            if ($metadata->getTableName() === $tableName) {
                return $metadata;
            }
        }

        return null;
    }

    /**
     * 构建字段的 Markdown 行
     *
     * @param ClassMetadata<object> $metadata
     */
    private function buildFieldsMarkdown(ClassMetadata $metadata): string
    {
        return implode('', array_map(
            fn ($fieldName, $mapping) => $this->buildSingleFieldMarkdown($fieldName, $mapping, $metadata),
            array_keys($metadata->fieldMappings),
            $metadata->fieldMappings
        ));
    }

    /**
     * 构建单个字段的 Markdown 行
     *
     * @param array<string, mixed>|FieldMapping $mapping
     * @param ClassMetadata<object> $metadata
     */
    private function buildSingleFieldMarkdown(string $fieldName, array|FieldMapping $mapping, ClassMetadata $metadata): string
    {
        $columnName = $this->namingStrategy->propertyToColumnName($fieldName, $metadata->name);
        $type = $this->getColumnType($mapping);
        $comment = $this->extractFieldComment($mapping);

        return "| {$columnName} | {$type} | {$comment} |\n";
    }

    /**
     * 获取数据库字段类型
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function getColumnType(array|FieldMapping $mapping): string
    {
        $type = is_array($mapping) ? ($mapping['type'] ?? '') : '';
        $type = is_string($type) ? $type : '';

        return '' === $type ? 'unknown' : ($this->getTypeMapping()[$type] ?? $type);
    }

    /**
     * 获取类型映射表
     *
     * @return array<string, string>
     */
    private function getTypeMapping(): array
    {
        return [
            'string' => 'varchar',
            'text' => 'text',
            'integer' => 'int',
            'smallint' => 'smallint',
            'bigint' => 'bigint',
            'boolean' => 'tinyint',
            'decimal' => 'decimal',
            'date' => 'date',
            'time' => 'time',
            'datetime' => 'datetime',
            'datetimetz' => 'datetime',
            'float' => 'float',
            'json' => 'json',
        ];
    }

    /**
     * 提取字段注释
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractFieldComment(array|FieldMapping $mapping): string
    {
        if (is_array($mapping)) {
            $options = $mapping['options'] ?? [];
            if (is_array($options)) {
                $comment = $options['comment'] ?? '';
                return is_string($comment) ? $comment : '';
            }
        }

        return '';
    }

    public function generateDatabaseMarkdown(): string
    {
        $entitiesMetadata = $this->getAllEntitiesMetadata();

        return implode('', array_map(fn ($metadata) => $this->generateTableMarkdown($metadata), $entitiesMetadata));
    }

    /**
     * 生成单个表的 Markdown
     *
     * @param array{
     *     tableName: string,
     *     comment: string,
     *     fields: array<string, array<string, mixed>>,
     *     associations: array<string, array<string, mixed>>
     * } $metadata
     */
    private function generateTableMarkdown(array $metadata): string
    {
        $tableName = $this->extractTableName($metadata);
        $tableComment = $this->extractTableComment($metadata);
        $fields = $this->extractFields($metadata);
        $associations = $this->extractAssociations($metadata);

        return "## {$tableName}\n" .
               "{$tableComment}\n\n" .
               $this->markdownBuilder->generateFieldsMarkdown($fields) .
               $this->markdownBuilder->generateAssociationsMarkdown($associations) .
               "\n---\n\n";
    }

    /**
     * 提取表名
     *
     * @param array<string, mixed> $metadata
     */
    private function extractTableName(array $metadata): string
    {
        $tableName = $metadata['tableName'] ?? '';
        if (!is_string($tableName)) {
            $tableName = is_scalar($tableName) ? (string) $tableName : '';
        }
        return $tableName;
    }

    /**
     * 提取表注释
     *
     * @param array<string, mixed> $metadata
     */
    private function extractTableComment(array $metadata): string
    {
        $comment = $metadata['comment'] ?? '无';

        return is_string($comment) ? $comment : '无';
    }

    /**
     * 提取字段数组
     *
     * @param array<string, mixed> $metadata
     * @return array<int, mixed>
     */
    private function extractFields(array $metadata): array
    {
        $fields = (array) ($metadata['fields'] ?? []);

        return array_values($fields);
    }

    /**
     * 提取关联关系数组
     *
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function extractAssociations(array $metadata): array
    {
        $associations = $metadata['associations'] ?? [];

        if (!is_array($associations)) {
            return [];
        }

        $result = [];
        foreach ($associations as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
