<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\Controller;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('controller:delete');
        $this->setAliases(['rm:controller']);
        $this->setDescription('Delete a controller from an application');
        $this->addArgument('app', InputArgument::REQUIRED, 'The name of the application');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the controller to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}
