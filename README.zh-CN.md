# Doctrine Entity Markdown Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-entity-markdown-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-entity-markdown-bundle)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)]()
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)]()

该 Bundle 用于从 Doctrine 实体生成 Markdown 格式的数据库字典。

## 功能

- 生成所有数据库表格的清单
- 为每个表格生成详细的字段信息，包括字段类型、长度、是否可空、默认值、注释等
- 处理实体之间的关联关系
- 支持 MCP（模型上下文协议）工具集成，用于 AI 助手

## 安装

```bash
composer require tourze/doctrine-entity-markdown-bundle
```

## 配置

在 `config/bundles.php` 中添加：

```php
return [
    // ...
    Tourze\DoctrineEntityMarkdownBundle\DoctrineEntityMarkdownBundle::class => ['all' => true],
];
```

## 系统要求

该包依赖以下组件：

- PHP ^8.1
- Symfony ^7.3
- Doctrine ORM ^3.0
- Doctrine Bundle ^2.13

## 使用方法

### 命令行

使用以下命令生成 Markdown 格式的数据字典：

```bash
bin/console doctrine:generate:markdown
```

### 在代码中使用

```php
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityServiceInterface;

class YourController
{
    public function __construct(
        private readonly EntityServiceInterface $entityService,
    ) {
    }
    
    public function generateDictionary()
    {
        // 获取所有表格清单
        $tableList = $this->entityService->getAllTableNames();
        
        // 获取特定实体的元数据
        $userMetadata = $this->entityService->getEntityMetadata('App\Entity\User');
        
        // 获取所有实体的元数据
        $allMetadata = $this->entityService->getAllEntitiesMetadata();
        
        // 生成完整的数据字典
        $fullMarkdown = $this->entityService->generateDatabaseMarkdown();
    }
}
```

## 高级用法

### MCP 工具集成

该包提供了 MCP (Model Context Protocol) 工具，可用于 AI 助手集成：

```php
use Tourze\DoctrineEntityMarkdownBundle\MCP\Tool\GetDatabaseDictionary;

// 通过 MCP 获取数据库字典
$tool = new GetDatabaseDictionary($entityService);
$result = $tool->execute();
```

### 自定义输出格式

你可以扩展 `EntityService` 类来自定义输出格式：

```php
class CustomEntityService extends EntityService
{
    public function generateCustomMarkdown(): string
    {
        // 自定义生成逻辑
        return $this->generateDatabaseMarkdown();
    }
}
```

## 测试

运行单元测试：

```bash
./vendor/bin/phpunit packages/doctrine-entity-markdown-bundle/tests
```

## 贡献

欢迎提交问题和拉取请求。请确保遵循现有的代码风格并为任何新功能添加测试。

## 许可证

本项目采用 MIT 许可证 - 详情请参阅 [LICENSE](../../LICENSE) 文件。
