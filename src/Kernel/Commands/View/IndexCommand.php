<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\View;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('view:list');
        $this->setAliases(['views']);
        $this->setDescription('List views in an application');
        $this->addArgument('app', InputArgument::OPTIONAL, 'The name of the application (lists all if omitted)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
