<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('app:create');
        $this->setAliases(['make:app']);
        $this->setDescription('Create a new application in the project');
        $this->addArgument('app', InputArgument::REQUIRED, 'The name of the application');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}