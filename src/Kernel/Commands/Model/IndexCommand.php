<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\Model;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('model:list');
        $this->setAliases(['models']);
        $this->setDescription('List models in an application');
        $this->addArgument('app', InputArgument::OPTIONAL, 'The name of the application (lists all if omitted)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
