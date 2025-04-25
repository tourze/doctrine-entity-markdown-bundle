# Doctrine Entity Markdown Bundle

该 Bundle 用于从 Doctrine 实体生成 Markdown 格式的数据字典。

## 功能

- 生成所有数据库表格的清单
- 为每个表格生成详细的字段信息，包括字段类型、长度、是否可空、默认值、注释等
- 处理实体之间的关联关系

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

## 使用方法

### 命令行

使用以下命令生成 Markdown 格式的数据字典：

```bash
bin/console doctrine:generate:markdown
```

### 在代码中使用

```php
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;

class YourController
{
    public function __construct(
        private readonly EntityService $entityService,
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

## 测试

运行单元测试：

```bash
./vendor/bin/phpunit packages/doctrine-entity-markdown-bundle/tests
```
