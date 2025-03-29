<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\NamingStrategy;

class EntityService
{
    private readonly NamingStrategy $namingStrategy;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->namingStrategy = $this->entityManager->getConfiguration()->getNamingStrategy();
    }

    /**
     * 获取所有实体的元数据
     *
     * @return array<string, array> 返回实体信息数组
     */
    public function getAllEntitiesMetadata(): array
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $result = [];

        /** @var ClassMetadata $metadata */
        foreach ($metadatas as $metadata) {
            $tableName = $metadata->getTableName();
            $result[$metadata->getName()] = [
                'tableName' => $tableName,
                'comment' => $metadata->table['options']['comment'] ?? '',
                'fields' => $this->getFieldsInfo($metadata),
                'associations' => $this->getAssociationsInfo($metadata),
            ];
        }

        return $result;
    }

    /**
     * 获取单个实体的元数据
     */
    public function getEntityMetadata(string $entityClass): ?array
    {
        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            return [
                'tableName' => $metadata->getTableName(),
                'comment' => $metadata->table['options']['comment'] ?? '',
                'fields' => $this->getFieldsInfo($metadata),
                'associations' => $this->getAssociationsInfo($metadata),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 获取字段信息
     */
    private function getFieldsInfo(ClassMetadata $metadata): array
    {
        $fields = [];

        // 处理主键
        foreach ($metadata->identifier as $idField) {
            if (isset($metadata->fieldMappings[$idField])) {
                $mapping = $metadata->fieldMappings[$idField];
                $fields[$idField] = $this->buildFieldInfo($mapping, $metadata, $idField, true);
            }
        }

        // 处理普通字段
        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            if (in_array($fieldName, $metadata->identifier)) {
                continue;
            }
            $fields[$fieldName] = $this->buildFieldInfo($mapping, $metadata, $fieldName);
        }

        return $fields;
    }

    /**
     * 构建字段信息
     */
    private function buildFieldInfo(array|FieldMapping $mapping, ClassMetadata $metadata, string $fieldName, bool $isPrimaryKey = false): array
    {
        $columnName = $this->namingStrategy->propertyToColumnName($fieldName, $metadata->name);
        $type = $this->getColumnType($mapping);
        $length = $mapping['length'] ?? $mapping['precision'] ?? null;
        $nullable = $mapping['nullable'] ?? false;

        // 处理默认值
        $default = $mapping['options']['default'] ?? null;
        if ($default instanceof \BackedEnum) {
            $default = $default->value;
        } elseif ($default instanceof \UnitEnum) {
            $default = $default->name;
        } elseif (is_object($default)) {
            $default = method_exists($default, '__toString') ? (string)$default : get_class($default);
        }

        $info = [
            'columnName' => $columnName,
            'type' => $type,
            'length' => $length,
            'nullable' => $nullable,
            'default' => $default,
            'comment' => $mapping['options']['comment'] ?? '',
            'isPrimaryKey' => $isPrimaryKey,
        ];

        // 处理枚举类型
        if (isset($mapping['enumType'])) {
            $enumClass = $mapping['enumType'];
            $cases = array_map(
                fn($case) => $case instanceof \BackedEnum ? $case->value : $case->name,
                $enumClass::cases()
            );
            $info['enum'] = [
                'class' => $enumClass,
                'values' => $cases,
            ];
        }

        // 完善注释
        $comment = $info['comment'] ?: '-';
        if ($info['isPrimaryKey']) {
            $comment .= ' (主键)';
        }
        if (isset($info['enum'])) {
            $comment .= sprintf(' (可选值: %s)', implode(', ', $info['enum']['values']));
        }
        $info['comment'] = $comment;

        return $info;
    }

    /**
     * 获取关联关系信息
     */
    private function getAssociationsInfo(ClassMetadata $metadata): array
    {
        $associations = [];

        foreach ($metadata->associationMappings as $fieldName => $mapping) {
            $targetMetadata = $this->entityManager->getClassMetadata($mapping['targetEntity']);
            $info = [
                'type' => $this->getRelationType($mapping['type']),
                'targetEntity' => $mapping['targetEntity'],
                'targetTable' => $targetMetadata->getTableName(),
            ];

            if (isset($mapping['joinColumns']) && !empty($mapping['joinColumns'])) {
                $info['joinColumns'] = array_map(function ($joinColumn) {
                    return [
                        'name' => $joinColumn['name'],
                        'referencedColumnName' => $joinColumn['referencedColumnName'],
                        'onDelete' => $joinColumn['onDelete'] ?? null,
                        'onUpdate' => $joinColumn['onUpdate'] ?? null,
                    ];
                }, $mapping['joinColumns']);
            }

            if (isset($mapping['joinTable']) && isset($mapping['joinTable']['name'])) {
                $info['joinTable'] = [
                    'name' => $mapping['joinTable']['name'],
                    'joinColumns' => $mapping['joinTable']['joinColumns'] ?? [],
                    'inverseJoinColumns' => $mapping['joinTable']['inverseJoinColumns'] ?? [],
                ];
            }

            $associations[$fieldName] = $info;
        }

        return $associations;
    }

    /**
     * 获取数据库字段类型
     */
    private function getColumnType(array|FieldMapping $mapping): string
    {
        $type = $mapping['type'];

        $typeMap = [
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

        return $typeMap[$type] ?? $type;
    }

    /**
     * 获取关联关系类型
     */
    private function getRelationType(int $type): string
    {
        return match($type) {
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
        $markdown = "# 数据库表清单\n\n";
        $markdown .= "| 表名 | 说明 |\n";
        $markdown .= "|------|------|\n";

        /** @var ClassMetadata $metadata */
        foreach ($metadatas as $metadata) {
            $tableName = $metadata->getTableName();
            $comment = $metadata->table['options']['comment'] ?? '-';
            $markdown .= "| {$tableName} | {$comment} |\n";
        }

        return $markdown;
    }

    /**
     * 获取指定表的字段和注释的markdown格式文本
     */
    public function getTableFields(string $tableName): string
    {
        $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $markdown = "# {$tableName}\n\n";
        $markdown .= "| 字段名 | 类型 | 说明 |\n";
        $markdown .= "|--------|------|------|\n";

        /** @var ClassMetadata $metadata */
        foreach ($metadatas as $metadata) {
            if ($metadata->getTableName() === $tableName) {
                foreach ($metadata->fieldMappings as $fieldName => $mapping) {
                    $columnName = $this->namingStrategy->propertyToColumnName($fieldName, $metadata->name);
                    $type = $this->getColumnType($mapping);
                    $comment = $mapping['options']['comment'] ?? '-';
                    $markdown .= "| {$columnName} | {$type} | {$comment} |\n";
                }
                return $markdown;
            }
        }

        return "# {$tableName}\n\n表不存在";
    }

    public function generateDatabaseMarkdown(): string
    {
        $entitiesMetadata = $this->getAllEntitiesMetadata();
        $markdown = '';

        foreach ($entitiesMetadata as $entityClass => $metadata) {
            $tableName = $metadata['tableName'];
            $markdown .= "## {$tableName}\n";

            // 获取表注释
            $tableComment = $metadata['comment'] ?: '无';
            $markdown .= "{$tableComment}\n\n";

            $markdown .= "### 字段\n";
            $markdown .= "| 名称 | 类型 | 长度 | 允许空 | 默认值 | 说明 |\n";
            $markdown .= "|--------|------|------|--------|--------|------|";

            // 输出所有字段
            foreach ($metadata['fields'] as $fieldName => $field) {
                $length = $field['length'] ?? '-';
                $nullable = $field['nullable'] ? 'Y' : 'N';
                $default = $field['default'] ?? '-';
                $comment = $field['comment'] ?: '-';

                $markdown .= "\n| {$field['columnName']} | {$field['type']} | {$length} | {$nullable} | {$default} | {$comment} |";
            }

            // 关联关系说明
            if (!empty($metadata['associations'])) {
                $markdown .= "\n\n### 关系\n";

                foreach ($metadata['associations'] as $fieldName => $association) {
                    if (isset($association['joinColumns'])) {
                        foreach ($association['joinColumns'] as $joinColumn) {
                            $markdown .= "- {$association['type']}：本表 `{$joinColumn['name']}` 关联 `{$association['targetTable']}` 的 `{$joinColumn['referencedColumnName']}`\n";
                        }
                    } elseif (isset($association['joinTable'])) {
                        $markdown .= "- {$association['type']}：与 `{$association['targetTable']}` 通过中间表 `{$association['joinTable']['name']}` 关联\n";
                    } else {
                        $markdown .= "- {$association['type']}：与 `{$association['targetTable']}` 关联\n";
                    }
                }
            }

            $markdown .= "\n---\n\n";
        }

        return $markdown;
    }
}
