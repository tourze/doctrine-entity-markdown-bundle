<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Service;

/**
 * 构建Markdown输出的辅助类
 */
final class MarkdownBuilder
{
    /**
     * 构建字段表格头部
     */
    public function getFieldsMarkdownHeader(): string
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
    public function generateSingleFieldMarkdownRow(array $field): string
    {
        $fieldData = $this->extractFieldDisplayData($field);

        $columnName = $this->safelyConvertToString($fieldData['columnName'] ?? '');
        $type = $this->safelyConvertToString($fieldData['type'] ?? '');
        $length = $this->safelyConvertToString($fieldData['length'] ?? '');
        $nullable = $this->safelyConvertToString($fieldData['nullable'] ?? '');
        $default = $this->safelyConvertToString($fieldData['default'] ?? '');
        $comment = $this->safelyConvertToString($fieldData['comment'] ?? '');

        return "\n| {$columnName} | {$type} | {$length} | {$nullable} | {$default} | {$comment} |";
    }

    /**
     * 提取字段显示数据
     *
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function extractFieldDisplayData(array $field): array
    {
        return [
            'columnName' => $this->safelyConvertToString($field['columnName'] ?? ''),
            'type' => $this->safelyConvertToString($field['type'] ?? ''),
            'length' => $this->formatFieldLength($field['length'] ?? null),
            'nullable' => $this->formatNullable($field['nullable'] ?? false),
            'default' => $this->formatDefaultValue($field['default'] ?? null),
            'comment' => $this->safelyConvertToString($field['comment'] ?? '-'),
        ];
    }

    /**
     * 格式化字段长度
     */
    private function formatFieldLength(mixed $length): string
    {
        return null !== $length ? \is_scalar($length) ? (string) $length : \gettype($length) : '-';
    }

    /**
     * 格式化是否允许为空
     */
    private function formatNullable(mixed $nullable): string
    {
        return (bool) $nullable ? 'Y' : 'N';
    }

    /**
     * 格式化默认值
     */
    private function formatDefaultValue(mixed $default): string
    {
        return null === $default ? '-' : $this->safelyConvertToString($default);
    }

    /**
     * 安全地转换为字符串
     */
    private function safelyConvertToString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string) $value : get_class($value);
        }

        return '';
    }

    /**
     * 生成字段表格 Markdown
     *
     * @param array<int, array<string, mixed>> $fields
     */
    public function generateFieldsMarkdown(array $fields): string
    {
        return $this->getFieldsMarkdownHeader() .
               implode('', array_map(fn ($field) => $this->generateSingleFieldMarkdownRow($field), $fields));
    }

    /**
     * 生成关联关系 Markdown
     *
     * @param array<string, mixed> $associations
     */
    public function generateAssociationsMarkdown(array $associations): string
    {
        if ([] === $associations) {
            return '';
        }

        $associationMarkdown = [];
        foreach ($associations as $association) {
            if (is_array($association)) {
                $associationMarkdown[] = $this->generateSingleAssociationMarkdown($association);
            }
        }

        return "\n\n### 关系\n" . implode('', $associationMarkdown);
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
        $associationType = $this->safelyConvertToString($association['type'] ?? '');
        $targetTable = $this->safelyConvertToString($association['targetTable'] ?? '');

        return "- {$associationType}：与 `{$targetTable}` 通过中间表 `{$joinTableName}` 关联\n";
    }

    /**
     * 生成直接关联的 Markdown
     *
     * @param array<string, mixed> $association
     */
    private function generateDirectAssociationMarkdown(array $association): string
    {
        $associationType = $this->safelyConvertToString($association['type'] ?? '');
        $targetTable = $this->safelyConvertToString($association['targetTable'] ?? '');

        return "- {$associationType}：与 `{$targetTable}` 关联\n";
    }

    /**
     * 提取 join table 名称
     */
    private function extractJoinTableName(mixed $joinTable): string
    {
        if (!is_array($joinTable)) {
            return '';
        }

        return $this->safelyConvertToString($joinTable['name'] ?? '');
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

        return array_values(is_array($joinColumns) ? $joinColumns : []);
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

        $joinColumnName = $this->safelyConvertToString($joinColumn['name'] ?? '');
        $referencedColumnName = $this->safelyConvertToString($joinColumn['referencedColumnName'] ?? '');
        $associationType = $this->safelyConvertToString($association['type'] ?? '');
        $targetTable = $this->safelyConvertToString($association['targetTable'] ?? '');

        return "- {$associationType}：本表 `{$joinColumnName}` 关联 `{$targetTable}` 的 `{$referencedColumnName}`\n";
    }
}