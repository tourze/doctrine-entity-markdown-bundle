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
    private EntityServiceInterface $entityService;

    protected function onSetUp(): void
    {
        $this->entityService = $this->createMock(EntityServiceInterface::class);
        self::getContainer()->set(EntityServiceInterface::class, $this->entityService);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(GenerateEntityMarkdownCommand::class);

        return new CommandTester($command);
    }

    public function testExecute(): void
    {
        $mockMarkdown = "## 表格内容\n表格详情";
        $this->entityService->method('generateDatabaseMarkdown')->willReturn($mockMarkdown);

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('# 数据库字典', $output);
        $this->assertStringContainsString($mockMarkdown, $output);
    }
}
