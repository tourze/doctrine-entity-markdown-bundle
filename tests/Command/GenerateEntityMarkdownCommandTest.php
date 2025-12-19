<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\DoctrineEntityMarkdownBundle\Command\GenerateEntityMarkdownCommand;
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
        // 不需要 Mock，使用真实服务
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
        // 验证输出包含数据库字典标题
        $this->assertStringContainsString('# 数据库字典', $output);
        // 验证命令执行成功
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
