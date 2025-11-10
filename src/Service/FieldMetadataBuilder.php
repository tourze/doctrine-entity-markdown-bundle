<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FieldMapping;
use Doctrine\ORM\Mapping\NamingStrategy;

/**
 * 字段元数据构建器
 * 负责从 Doctrine 映射中提取和构建字段信息
 */
readonly class FieldMetadataBuilder
{
    public function __construct(
        private NamingStrategy $namingStrategy,
    ) {
    }

    /**
     * 获取字段信息
     *
     * @param ClassMetadata<object> $metadata
     * @return array<string, array<string, mixed>>
     */
    public function getFieldsInfo(ClassMetadata $metadata): array
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
        if (is_array($mapping)) {
            return $this->extractArrayOptions($mapping);
        }

        return $this->extractFieldMappingOptions($mapping);
    }

    /**
     * 从数组映射中提取选项
     *
     * @param array<string, mixed> $mapping
     * @return array<string, mixed>
     */
    private function extractArrayOptions(array $mapping): array
    {
        $options = $mapping['options'] ?? [];
        if (!is_array($options)) {
            return [];
        }

        $result = [];
        foreach ($options as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 从FieldMapping对象中提取选项
     *
     * @param FieldMapping $mapping
     * @return array<string, mixed>
     */
    private function extractFieldMappingOptions(FieldMapping $mapping): array
    {
        $mappingArray = (array) $mapping;
        /** @var array<string, mixed> $options */
        $options = $mappingArray['options'] ?? [];

        $result = [];
        foreach ($options as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 提取字段长度
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractLength(array|FieldMapping $mapping): ?int
    {
        if (is_array($mapping)) {
            $length = $mapping['length'] ?? $mapping['precision'] ?? null;
            return is_int($length) ? $length : null;
        }
        return null;
    }

    /**
     * 提取是否可为空
     *
     * @param array<string, mixed>|FieldMapping $mapping
     */
    private function extractNullable(array|FieldMapping $mapping): bool
    {
        if (is_array($mapping)) {
            $nullable = $mapping['nullable'] ?? false;
            return (bool) $nullable;
        }
        return false;
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
                fn ($case) => $case instanceof \BackedEnum ? $case->value : ($case->name ?? 'unknown'),
                is_subclass_of($enumClass, \UnitEnum::class) ? $enumClass::cases() : []
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
        $comment = $info['comment'] ?? '';
        return is_string($comment) && '' !== $comment ? $comment : '-';
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

        $enum = $info['enum'];
        if (!is_array($enum) || !isset($enum['values'])) {
            return $comment;
        }

        $values = $enum['values'];
        if (!is_array($values)) {
            return $comment;
        }

        return $comment . sprintf(' (可选值: %s)', implode(', ', $values));
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
}
