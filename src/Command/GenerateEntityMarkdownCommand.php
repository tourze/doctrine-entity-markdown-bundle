<?php

declare(strict_types=1);

namespace Tourze\DoctrineEntityMarkdownBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityServiceInterface;

#[AsCommand(
    name: self::NAME,
    description: 'Generate database dictionary in markdown format',
)]
class GenerateEntityMarkdownCommand extends Command
{
    public const NAME = 'doctrine:generate:markdown';

    public function __construct(
        private readonly EntityServiceInterface $entityService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $markdown = "# 数据库字典\n\n" . $this->entityService->generateDatabaseMarkdown();

        $output->write($markdown);

        return Command::SUCCESS;
    }
}
