<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\NamingStrategy;

/**
 * @template TEntity of object
 */
readonly class EntityService implements EntityServiceInterface
{
    private NamingStrategy $namingStrategy;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->namingStrategy = $this->entityManager->getConfiguration()->getNamingStrategy();
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
            'fields' => $this->getFieldsInfo($metadata),
            'associations' => $this->getAssociationsInfo($metadata),
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
     * 获取字段信息
     *
     * @param ClassMetadata<object> $metadata
     * @return array<string, array<string, mixed>>
     */
    private function getFieldsInfo(ClassMetadata $metadata): array
    {
        return array_merge(
            $this->buildPrimaryKeyFields($metadata),
            $this->buildRegularFields($metadata)
        );
    }

    /**
     * 构建主键字段
     *
     * @param ClassMetadata<object> $metadata
     * @return array<string, array<string, mixed>>
     */
    private function buildPrimaryKeyFields(ClassMetadata $metadata): array
    {
        $fields = [];
        foreach ($metadata->identifier as $idField) {
            if (isset($metadata->fieldMappings[$idField])) {
                $mapping = $metadata->fieldMappings[$idField];
                $fields[$idField] = $this->buildFieldInfo($mapping, $metadata, $idField, true);
            }
        }

        return $fields;
    }

    /**
     * 构建普通字段
     *
     * @param ClassMetadata<object> $metadata
     * @return array<string, array<string, mixed>>
     */
    private function buildRegularFields(ClassMetadata $metadata): array
    {
        $fields = [];
        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            if (!in_array($fieldName, $metadata->identifier, true)) {
                $fields[$fieldName] = $this->buildFieldInfo($mapping, $metadata, $fieldName);
            }
        }

        return $fields;
    }

    /**
     * 构建字段信息
     *
     * @param array<string, mixed>|FieldMapping $mapping
     * @param ClassMetadata<object> $metadata
     * @return array<string, mixed>
     */
    private function buildFieldInfo(array|FieldMapping $mapping, ClassMetadata $metadata, string $fieldName, bool $isPrimaryKey = false): array
    {
        $info = $this->createBaseFieldInfo($mapping, $metadata, $fieldName, $isPrimaryKey);
        $info = $this->addEnumInfo($info, $mapping);

        return $this->enhanceComment($info);
    }

    /**
     * 创建基础字段信息
     *
     * @param array<string, mixed>|FieldMapping $mapping
     * @param ClassMetadata<object> $metadata
     * @return array{
     *     columnName: string,
     *     type: string,
     *     length: int|null,
     *     nullable: bool,
     *     default: mixed,
     *     comment: string,
     *     isPrimaryKey: bool
     * }
     */
    private function createBaseFieldInfo(array|FieldMapping $mapping, ClassMetadata $metadata, string $fieldName, bool $isPrimaryKey): array
    {
        $columnName = $this->namingStrategy->propertyToColumnName($fieldName, $metadata->name);
        $options = $this->extractMappingOptions($mapping);

        return [
            'columnName' => $columnName,
            'type' => $this->getColumnType($mapping),
            'length' => $this->extractLength($mapping),
            'nullable' => $this->extractNullable($mapping),
            'default' => $this->extractDefaultValue($mapping),
            'comment' => $this->extractComment($options),
            'isPrimaryKey' => $isPrimaryKey,
        ];
    }

    /**
     * 提取映射选项
     *
     * @param array<string, mixed>|FieldMapping $mapping
     * @return array<string, mixed>
     */
    private function extractMappingOptions(array|FieldMapping $mapping): array
    {
        return is_array($mapping) ? ($mapping['options'] ?? []) : [];
    }

    /**
     * 提取字段长度
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractLength(array|FieldMapping $mapping): ?int
    {
        return $mapping['length'] ?? $mapping['precision'] ?? null;
    }

    /**
     * 提取是否可为空
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractNullable(array|FieldMapping $mapping): bool
    {
        return $mapping['nullable'] ?? false;
    }

    /**
     * 提取注释
     *
     * @param array<string, mixed> $options
     */
    private function extractComment(array $options): string
    {
        $comment = $options['comment'] ?? '';

        return is_string($comment) ? $comment : '';
    }

    /**
     * 提取默认值
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractDefaultValue(array|FieldMapping $mapping): mixed
    {
        $options = is_array($mapping) ? ($mapping['options'] ?? []) : [];
        $default = is_array($options) ? ($options['default'] ?? null) : null;

        return $this->normalizeDefaultValue($default);
    }

    /**
     * 标准化默认值
     */
    private function normalizeDefaultValue(mixed $default): mixed
    {
        return match (true) {
            $default instanceof \BackedEnum => $default->value,
            $default instanceof \UnitEnum => $default->name,
            is_object($default) => method_exists($default, '__toString') ? (string) $default : get_class($default),
            default => $default,
        };
    }

    /**
     * 添加枚举信息
     *
     * @param array<string, mixed> $info
     * @param array<string, mixed>|FieldMapping $mapping
     * @return array<string, mixed>
     */
    private function addEnumInfo(array $info, array|FieldMapping $mapping): array
    {
        $enumInfo = $this->extractEnumInfo($mapping);

        return null !== $enumInfo ? array_merge($info, ['enum' => $enumInfo]) : $info;
    }

    /**
     * 提取枚举信息
     *
     * @param array<string, mixed>|FieldMapping $mapping
     * @return array{class: string, values: array<mixed>}|null
     */
    private function extractEnumInfo(array|FieldMapping $mapping): ?array
    {
        $enumClass = $mapping['enumType'] ?? null;

        if (!is_string($enumClass) || !class_exists($enumClass)) {
            return null;
        }

        return [
            'class' => $enumClass,
            'values' => array_map(
                fn ($case) => $case instanceof \BackedEnum ? $case->value : $case->name,
                $enumClass::cases()
            ),
        ];
    }

    /**
     * 完善注释
     *
     * @param array<string, mixed> $info
     * @return array<string, mixed>
     */
    private function enhanceComment(array $info): array
    {
        $comment = $this->buildBaseComment($info);
        $comment = $this->appendPrimaryKeyTag($comment, $info);
        $comment = $this->appendEnumValues($comment, $info);

        $info['comment'] = $comment;

        return $info;
    }

    /**
     * 构建基础注释
     *
     * @param array<string, mixed> $info
     */
    private function buildBaseComment(array $info): string
    {
        return '' !== $info['comment'] ? $info['comment'] : '-';
    }

    /**
     * 添加主键标签
     *
     * @param array<string, mixed> $info
     */
    private function appendPrimaryKeyTag(string $comment, array $info): string
    {
        return (bool) ($info['isPrimaryKey'] ?? false) ? $comment . ' (主键)' : $comment;
    }

    /**
     * 添加枚举值信息
     *
     * @param array<string, mixed> $info
     */
    private function appendEnumValues(string $comment, array $info): string
    {
        if (!isset($info['enum'])) {
            return $comment;
        }

        return $comment . sprintf(' (可选值: %s)', implode(', ', $info['enum']['values']));
    }

    /**
     * 获取关联关系信息
     *
     * @param ClassMetadata<object> $metadata
     * @return array<string, array<string, mixed>>
     */
    private function getAssociationsInfo(ClassMetadata $metadata): array
    {
        $associations = [];

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            $associationInfo = $this->processAssociationMapping($mapping);
            if (null !== $associationInfo) {
                $associations[$fieldName] = $associationInfo;
            }
        }

        return $associations;
    }

    /**
     * 处理单个关联映射
     *
     * @param mixed $mapping
     * @return array<string, mixed>|null
     */
    private function processAssociationMapping(mixed $mapping): ?array
    {
        return is_array($mapping) ? $this->buildAssociationInfo($mapping) : null;
    }

    /**
     * 构建单个关联关系信息
     *
     * @param array<string, mixed> $mapping
     * @return array<string, mixed>|null
     */
    private function buildAssociationInfo(array $mapping): ?array
    {
        if (!$this->isValidAssociationMapping($mapping)) {
            return null;
        }

        $targetEntity = $this->extractTargetEntity($mapping);
        if (null === $targetEntity) {
            return null;
        }

        return $this->createCompleteAssociationInfo($mapping, $targetEntity);
    }

    /**
     * 创建完整的关联关系信息
     *
     * @param array<string, mixed> $mapping
     * @param class-string $targetEntity
     * @return array<string, mixed>
     */
    private function createCompleteAssociationInfo(array $mapping, string $targetEntity): array
    {
        $info = $this->createBaseAssociationInfo($mapping, $targetEntity);
        $info = $this->addJoinColumnsToInfo($info, $mapping);

        return $this->addJoinTableToInfo($info, $mapping);
    }

    /**
     * 检查是否为有效的关联映射
     *
     * @param mixed $mapping
     */
    private function isValidAssociationMapping(mixed $mapping): bool
    {
        return is_array($mapping);
    }

    /**
     * 创建基础关联关系信息
     *
     * @param array<string, mixed> $mapping
     * @param class-string $targetEntity
     * @return array{
     *     type: string,
     *     targetEntity: class-string,
     *     targetTable: string
     * }
     */
    private function createBaseAssociationInfo(array $mapping, string $targetEntity): array
    {
        $targetMetadata = $this->entityManager->getClassMetadata($targetEntity);
        $relationType = $this->extractRelationType($mapping);

        return [
            'type' => $relationType,
            'targetEntity' => $targetEntity,
            'targetTable' => $targetMetadata->getTableName(),
        ];
    }

    /**
     * 提取关联关系类型
     *
     * @param array<string, mixed> $mapping
     */
    private function extractRelationType(array $mapping): string
    {
        $type = $mapping['type'] ?? null;

        return is_int($type) ? $this->getRelationType($type) : '未知';
    }

    /**
     * 提取目标实体类名
     *
     * @param array<string, mixed> $mapping
     * @return class-string|null
     */
    private function extractTargetEntity(array $mapping): ?string
    {
        $targetEntity = $mapping['targetEntity'] ?? '';
        if (!is_string($targetEntity) || !class_exists($targetEntity)) {
            return null;
        }

        return $targetEntity;
    }

    /**
     * 添加 join columns 信息到关联信息中
     *
     * @param array<string, mixed> $info
     * @param array<string, mixed> $mapping
     * @return array<string, mixed>
     */
    private function addJoinColumnsToInfo(array $info, array $mapping): array
    {
        $joinColumns = $mapping['joinColumns'] ?? [];
        if (!is_array($joinColumns) || [] === $joinColumns) {
            return $info;
        }

        $info['joinColumns'] = array_map(fn ($joinColumn) => $this->normalizeJoinColumn($joinColumn), $joinColumns);

        return $info;
    }

    /**
     * 添加 join table 信息到关联信息中
     *
     * @param array<string, mixed> $info
     * @param array<string, mixed> $mapping
     * @return array<string, mixed>
     */
    private function addJoinTableToInfo(array $info, array $mapping): array
    {
        $joinTable = $mapping['joinTable'] ?? null;
        if (!$this->isValidJoinTable($joinTable)) {
            return $info;
        }

        $info['joinTable'] = $this->buildJoinTableInfo($joinTable);

        return $info;
    }

    /**
     * 检查是否为有效的 join table 配置
     *
     * @param mixed $joinTable
     */
    private function isValidJoinTable(mixed $joinTable): bool
    {
        return is_array($joinTable) && isset($joinTable['name']);
    }

    /**
     * 构建 join table 信息
     *
     * @param array<string, mixed> $joinTable
     * @return array{
     *     name: string,
     *     joinColumns: array<int, array{
     *         name: string,
     *         referencedColumnName: string,
     *         onDelete: string|null,
     *         onUpdate: string|null
     *     }>,
     *     inverseJoinColumns: array<int, array{
     *         name: string,
     *         referencedColumnName: string,
     *         onDelete: string|null,
     *         onUpdate: string|null
     *     }>
     * }
     */
    private function buildJoinTableInfo(array $joinTable): array
    {
        return [
            'name' => (string) $joinTable['name'],
            'joinColumns' => $this->normalizeJoinColumns($joinTable['joinColumns'] ?? []),
            'inverseJoinColumns' => $this->normalizeJoinColumns($joinTable['inverseJoinColumns'] ?? []),
        ];
    }

    /**
     * 标准化 join columns 数组
     *
     * @param mixed $columns
     * @return array<int, array{
     *     name: string,
     *     referencedColumnName: string,
     *     onDelete: string|null,
     *     onUpdate: string|null
     * }>
     */
    private function normalizeJoinColumns(mixed $columns): array
    {
        if (!is_array($columns)) {
            return [];
        }

        return array_map(fn ($column) => $this->normalizeJoinColumn($column), $columns);
    }

    /**
     * 标准化 join column 信息
     *
     * @param mixed $joinColumn
     * @return array{
     *     name: string,
     *     referencedColumnName: string,
     *     onDelete: string|null,
     *     onUpdate: string|null
     * }
     */
    private function normalizeJoinColumn(mixed $joinColumn): array
    {
        return is_array($joinColumn) ? $this->buildJoinColumnFromArray($joinColumn) : $this->getDefaultJoinColumn();
    }

    /**
     * 从数组构建 join column
     *
     * @param array<string, mixed> $joinColumn
     * @return array{
     *     name: string,
     *     referencedColumnName: string,
     *     onDelete: string|null,
     *     onUpdate: string|null
     * }
     */
    private function buildJoinColumnFromArray(array $joinColumn): array
    {
        return [
            'name' => (string) ($joinColumn['name'] ?? ''),
            'referencedColumnName' => (string) ($joinColumn['referencedColumnName'] ?? ''),
            'onDelete' => $this->normalizeJoinAction($joinColumn['onDelete'] ?? null),
            'onUpdate' => $this->normalizeJoinAction($joinColumn['onUpdate'] ?? null),
        ];
    }

    /**
     * 获取默认 join column 配置
     *
     * @return array{
     *     name: string,
     *     referencedColumnName: string,
     *     onDelete: string|null,
     *     onUpdate: string|null
     * }
     */
    private function getDefaultJoinColumn(): array
    {
        return [
            'name' => '',
            'referencedColumnName' => '',
            'onDelete' => null,
            'onUpdate' => null,
        ];
    }

    /**
     * 标准化 join 动作
     *
     * @param mixed $action
     */
    private function normalizeJoinAction(mixed $action): ?string
    {
        return is_string($action) ? $action : null;
    }

    /**
     * 获取数据库字段类型
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function getColumnType(array|FieldMapping $mapping): string
    {
        $type = $this->extractTypeFromMapping($mapping);

        return $this->mapToColumnType($type);
    }

    /**
     * 从映射中提取类型
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractTypeFromMapping(array|FieldMapping $mapping): string
    {
        $type = is_array($mapping) ? ($mapping['type'] ?? '') : '';

        return is_string($type) ? $type : '';
    }

    /**
     * 映射到数据库列类型
     */
    private function mapToColumnType(string $type): string
    {
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
     * 获取关联关系类型
     */
    private function getRelationType(int $type): string
    {
        return match ($type) {
            ClassMetadata::ONE_TO_ONE => '一对一',
            ClassMetadata::MANY_TO_ONE => '多对一',
            ClassMetadata::ONE_TO_MANY => '一对多',
            ClassMetadata::MANY_TO_MANY => '多对多',
            default => '未知',
        };
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
     * 提取字段注释
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractFieldComment(array|FieldMapping $mapping): string
    {
        $options = $this->extractMappingOptions($mapping);

        return $this->extractComment($options);
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
        $tableInfo = $this->extractTableInfo($metadata);

        return $this->buildCompleteTableMarkdown($tableInfo);
    }

    /**
     * 提取表信息
     *
     * @param array<string, mixed> $metadata
     * @return array{
     *     tableName: string,
     *     tableComment: string,
     *     fields: array<int, mixed>,
     *     associations: array<string, mixed>
     * }
     */
    private function extractTableInfo(array $metadata): array
    {
        return [
            'tableName' => $this->extractTableName($metadata),
            'tableComment' => $this->extractTableComment($metadata),
            'fields' => $this->extractFields($metadata),
            'associations' => $this->extractAssociations($metadata),
        ];
    }

    /**
     * 提取表名
     *
     * @param array<string, mixed> $metadata
     */
    private function extractTableName(array $metadata): string
    {
        return (string) ($metadata['tableName'] ?? '');
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
        return (array) ($metadata['associations'] ?? []);
    }

    /**
     * 构建表的完整 Markdown
     *
     * @param array{
     *     tableName: string,
     *     tableComment: string,
     *     fields: array<int, mixed>,
     *     associations: array<string, mixed>
     * } $tableInfo
     */
    private function buildCompleteTableMarkdown(array $tableInfo): string
    {
        return "## {$tableInfo['tableName']}\n" .
               "{$tableInfo['tableComment']}\n\n" .
               $this->generateFieldsMarkdown($tableInfo['fields']) .
               $this->generateAssociationsMarkdown($tableInfo['associations']) .
               "\n---\n\n";
    }

    /**
     * 生成字段表格 Markdown
     *
     * @param array<int, array<string, mixed>> $fields
     */
    private function generateFieldsMarkdown(array $fields): string
    {
        return $this->getFieldsMarkdownHeader() .
               implode('', array_map(fn ($field) => $this->generateSingleFieldMarkdownRow($field), $fields));
    }

    /**
     * 获取字段表格头部
     */
    private function getFieldsMarkdownHeader(): string
    {
        return "### 字段\n" .
               "| 名称 | 类型 | 长度 | 允许空 | 默认值 | 说明 |\n" .
               '|--------|------|------|--------|--------|------|';
    }

    /**
     * 生成单个字段的 Markdown 行
     *
     * @param array<string, mixed> $field
     */
    private function generateSingleFieldMarkdownRow(array $field): string
    {
        $fieldData = $this->extractFieldDisplayData($field);

        return "\n| {$fieldData['columnName']} | {$fieldData['type']} | {$fieldData['length']} | {$fieldData['nullable']} | {$fieldData['default']} | {$fieldData['comment']} |";
    }

    /**
     * 提取字段显示数据
     *
     * @param array<string, mixed> $field
     * @return array<string, string>
     */
    private function extractFieldDisplayData(array $field): array
    {
        return [
            'columnName' => (string) $field['columnName'],
            'type' => (string) $field['type'],
            'length' => $this->formatFieldLength($field['length'] ?? null),
            'nullable' => $this->formatNullable($field['nullable'] ?? false),
            'default' => $this->formatDefaultValue($field['default'] ?? null),
            'comment' => (string) ($field['comment'] ?? '-'),
        ];
    }

    /**
     * 格式化字段长度
     */
    private function formatFieldLength(?int $length): string
    {
        return null !== $length ? (string) $length : '-';
    }

    /**
     * 格式化是否允许为空
     */
    private function formatNullable(bool $nullable): string
    {
        return $nullable ? 'Y' : 'N';
    }

    /**
     * 格式化默认值
     */
    private function formatDefaultValue(mixed $default): string
    {
        return null === $default ? '-' : (string) $default;
    }

    /**
     * 生成关联关系 Markdown
     *
     * @param array<string, array<string, mixed>> $associations
     */
    private function generateAssociationsMarkdown(array $associations): string
    {
        if ([] === $associations) {
            return '';
        }

        return "\n\n### 关系\n" .
               implode('', array_map(fn ($association) => $this->generateSingleAssociationMarkdown($association), $associations));
    }

    /**
     * 生成单个关联关系 Markdown
     *
     * @param array<string, mixed> $association
     */
    private function generateSingleAssociationMarkdown(array $association): string
    {
        return match (true) {
            $this->hasJoinColumns($association) => $this->generateJoinColumnsMarkdown($association),
            $this->hasJoinTable($association) => $this->generateJoinTableMarkdown($association),
            default => $this->generateDirectAssociationMarkdown($association),
        };
    }

    /**
     * 检查是否有 join columns
     *
     * @param array<string, mixed> $association
     */
    private function hasJoinColumns(array $association): bool
    {
        return isset($association['joinColumns']);
    }

    /**
     * 检查是否有 join table
     *
     * @param array<string, mixed> $association
     */
    private function hasJoinTable(array $association): bool
    {
        return isset($association['joinTable']);
    }

    /**
     * 生成 join table 关联的 Markdown
     *
     * @param array<string, mixed> $association
     */
    private function generateJoinTableMarkdown(array $association): string
    {
        $joinTableName = $this->extractJoinTableName($association['joinTable'] ?? []);

        return "- {$association['type']}：与 `{$association['targetTable']}` 通过中间表 `{$joinTableName}` 关联\n";
    }

    /**
     * 生成直接关联的 Markdown
     *
     * @param array<string, mixed> $association
     */
    private function generateDirectAssociationMarkdown(array $association): string
    {
        return "- {$association['type']}：与 `{$association['targetTable']}` 关联\n";
    }

    /**
     * 提取 join table 名称
     */
    private function extractJoinTableName(mixed $joinTable): string
    {
        if (!is_array($joinTable)) {
            return '';
        }

        return (string) ($joinTable['name'] ?? '');
    }

    /**
     * 生成 join columns 的 Markdown
     *
     * @param array<string, mixed> $association
     */
    private function generateJoinColumnsMarkdown(array $association): string
    {
        $joinColumns = $this->extractJoinColumns($association);

        return implode('', array_map(
            fn ($joinColumn) => $this->generateJoinColumnMarkdown($association, $joinColumn),
            $joinColumns
        ));
    }

    /**
     * 提取 join columns
     *
     * @param array<string, mixed> $association
     * @return array<int, mixed>
     */
    private function extractJoinColumns(array $association): array
    {
        $joinColumns = $association['joinColumns'] ?? [];

        return is_array($joinColumns) ? $joinColumns : [];
    }

    /**
     * 生成单个 join column 的 Markdown
     *
     * @param array<string, mixed> $association
     * @param mixed $joinColumn
     */
    private function generateJoinColumnMarkdown(array $association, mixed $joinColumn): string
    {
        if (!is_array($joinColumn)) {
            return '';
        }

        $joinColumnName = $joinColumn['name'] ?? '';
        $referencedColumnName = $joinColumn['referencedColumnName'] ?? '';

        return "- {$association['type']}：本表 `{$joinColumnName}` 关联 `{$association['targetTable']}` 的 `{$referencedColumnName}`\n";
    }
}
