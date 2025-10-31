# Doctrine Entity Markdown Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/doctrine-entity-markdown-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/doctrine-entity-markdown-bundle)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen)]()
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)]()

A Symfony Bundle for generating Markdown format database dictionaries from Doctrine entities.

## Features

- Generate complete database table listings
- Generate detailed field information for each table including field types, length, nullable status, default values, comments, etc.
- Handle associations between entities
- Support MCP (Model Context Protocol) tool integration for AI assistants

## Installation

```bash
composer require tourze/doctrine-entity-markdown-bundle
```

## Configuration

Add the bundle to your `config/bundles.php`:

```php
return [
    // ...
    Tourze\DoctrineEntityMarkdownBundle\DoctrineEntityMarkdownBundle::class => ['all' => true],
];
```

## Requirements

This package requires the following components:

- PHP ^8.1
- Symfony ^7.3
- Doctrine ORM ^3.0
- Doctrine Bundle ^2.13

## Quick Start

### Command Line Usage

Generate Markdown format database dictionary using the following command:

```bash
bin/console doctrine:generate:markdown
```

### Usage in Code

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
        // Get all table names
        $tableList = $this->entityService->getAllTableNames();
        
        // Get metadata for a specific entity
        $userMetadata = $this->entityService->getEntityMetadata('App\Entity\User');
        
        // Get metadata for all entities
        $allMetadata = $this->entityService->getAllEntitiesMetadata();
        
        // Generate complete database dictionary
        $fullMarkdown = $this->entityService->generateDatabaseMarkdown();
    }
}
```

## Advanced Usage

### MCP Tool Integration

This package provides MCP (Model Context Protocol) tools for AI assistant integration:

```php
use Tourze\DoctrineEntityMarkdownBundle\MCP\Tool\GetDatabaseDictionary;

// Get database dictionary through MCP
$tool = new GetDatabaseDictionary($entityService);
$result = $tool->execute();
```

### Custom Output Format

You can extend the `EntityService` class to customize the output format:

```php
class CustomEntityService extends EntityService
{
    public function generateCustomMarkdown(): string
    {
        // Custom generation logic
        return $this->generateDatabaseMarkdown();
    }
}
```

## Testing

Run unit tests:

```bash
./vendor/bin/phpunit packages/doctrine-entity-markdown-bundle/tests
```

## Contributing

Please feel free to submit issues and pull requests. Make sure to follow the existing code style and add tests for any new functionality.

## License

This project is licensed under the MIT License - see the [LICENSE](../../LICENSE) file for details.
