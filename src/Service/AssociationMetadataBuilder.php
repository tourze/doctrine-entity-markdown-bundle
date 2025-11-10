<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * 关联关系元数据构建器
 * 负责从 Doctrine 映射中提取和构建关联关系信息
 */
readonly class AssociationMetadataBuilder
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 获取关联关系信息
     *
     * @param ClassMetadata<object> $metadata
     * @return array<string, array<string, mixed>>
     */
    public function getAssociationsInfo(ClassMetadata $metadata): array
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
        if (!is_array($mapping)) {
            return null;
        }

        /** @var array<string, mixed> $typedMapping */
        $typedMapping = $mapping;

        return $this->buildAssociationInfo($typedMapping);
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

        if (!is_array($joinTable)) {
            return $info;
        }

        /** @var array<string, mixed> $typedJoinTable */
        $typedJoinTable = $joinTable;

        $info['joinTable'] = $this->buildJoinTableInfo($typedJoinTable);

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
        $name = $joinTable['name'] ?? '';
        if (!is_string($name)) {
            $name = is_scalar($name) ? (string) $name : '';
        }

        return [
            'name' => $name,
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

        /** @var array<int, array{name: string, referencedColumnName: string, onDelete: string|null, onUpdate: string|null}> $result */
        $result = array_map(fn ($column) => $this->normalizeJoinColumn($column), $columns);

        return $result;
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
        if (!is_array($joinColumn)) {
            return $this->getDefaultJoinColumn();
        }

        /** @var array<string, mixed> $typedJoinColumn */
        $typedJoinColumn = $joinColumn;

        return $this->buildJoinColumnFromArray($typedJoinColumn);
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
        $name = $joinColumn['name'] ?? '';
        if (!is_string($name)) {
            $name = '';
        }

        $referencedColumnName = $joinColumn['referencedColumnName'] ?? '';
        if (!is_string($referencedColumnName)) {
            $referencedColumnName = '';
        }

        return [
            'name' => $name,
            'referencedColumnName' => $referencedColumnName,
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
}
