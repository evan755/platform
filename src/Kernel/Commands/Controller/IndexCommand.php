<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\Controller;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('controller:list');
        $this->setAliases(['controllers']);
        $this->setDescription('List controllers in an application');
        $this->addArgument('app', InputArgument::OPTIONAL, 'The name of the application (lists all if omitted)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
