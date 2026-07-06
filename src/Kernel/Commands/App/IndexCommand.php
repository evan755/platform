<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('app:list');
        $this->setAliases(['apps']);
        $this->setDescription('List applications in the project');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return Command::SUCCESS;
    }
}