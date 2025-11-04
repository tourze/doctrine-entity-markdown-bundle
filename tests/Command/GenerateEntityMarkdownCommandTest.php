<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\DoctrineEntityMarkdownBundle\Command\GenerateEntityMarkdownCommand;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityServiceInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(GenerateEntityMarkdownCommand::class)]
#[RunTestsInSeparateProcesses]
final class GenerateEntityMarkdownCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        $entityService = $this->createMock(EntityServiceInterface::class);
        $entityService->method('generateDatabaseMarkdown')->willReturn("## 数据库字典\n测试内容");

        // 注册Mock服务到容器
        static::getContainer()->set(EntityServiceInterface::class, $entityService);
    }

    protected function getCommandTester(): CommandTester
    {
        return new CommandTester(static::getService(GenerateEntityMarkdownCommand::class));
    }

    public function testExecute(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('# 数据库字典', $output);
        $this->assertStringContainsString('测试内容', $output);
    }
}
