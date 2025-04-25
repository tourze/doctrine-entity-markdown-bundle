<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\DoctrineEntityMarkdownBundle\Command\GenerateEntityMarkdownCommand;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;

class GenerateEntityMarkdownCommandTest extends TestCase
{
    private EntityService $entityService;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityService = $this->createMock(EntityService::class);

        $command = new GenerateEntityMarkdownCommand($this->entityService);

        $application = new Application();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        // 模拟 EntityService->generateDatabaseMarkdown 返回的数据
        $mockMarkdown = "## 表格内容\n表格详情";
        $this->entityService->method('generateDatabaseMarkdown')->willReturn($mockMarkdown);

        // 执行命令
        $this->commandTester->execute([]);

        // 验证输出是否包含预期内容
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('# 数据库字典', $output);
        $this->assertStringContainsString($mockMarkdown, $output);
    }
}
