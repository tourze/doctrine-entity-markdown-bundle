<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Service;

interface EntityServiceInterface
{
    /**
     * 获取所有实体的元数据
     *
     * @return array<string, mixed>
     */
    public function getAllEntitiesMetadata(): array;

    /**
     * 获取单个实体的元数据
     *
     * @return array<string, mixed>|null
     */
    public function getEntityMetadata(string $entityClass): ?array;

    /**
     * 获取所有表名和注释的markdown格式文本
     */
    public function getAllTableNames(): string;

    /**
     * 获取指定表的字段和注释的markdown格式文本
     */
    public function getTableFields(string $tableName): string;

    /**
     * 生成数据库markdown
     */
    public function generateDatabaseMarkdown(): string;
}
