<?php

namespace Tourze\DoctrineEntityMarkdownBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;

#[AsCommand(
    name: self::NAME,
    description: 'Generate database dictionary in markdown format',
)]
class GenerateEntityMarkdownCommand extends Command
{
    const NAME = 'doctrine:generate:markdown';

    public function __construct(
        private readonly EntityService $entityService,
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
