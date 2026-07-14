<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\Command;

use Evan755\Platform\Kernel\Repositories\AppRepository;
use Evan755\Platform\Kernel\Repositories\CommandRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('command:delete');
        $this->setAliases(['rm:command']);
        $this->setDescription('Delete a CLI command from an application');
        $this->addArgument('app', InputArgument::REQUIRED, 'The name of the application');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the command to delete');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = (string)$input->getArgument('app');
        $name = (string)$input->getArgument('name');
        $appRepository = new AppRepository();
        $commandRepository = new CommandRepository();

        if (!$appRepository->exists($app)) {
            $output->writeln("<error>Application $app does not exist</error>");
            return Command::FAILURE;
        }

        if (!$commandRepository->exists($app, $name)) {
            $output->writeln("<error>Command $name does not exist in $app</error>");
            return Command::FAILURE;
        }

        if (!$commandRepository->delete($app, $name)) {
            $output->writeln("<error>Failed to delete command $name from $app</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Command $name deleted from $app</info>");
        return Command::SUCCESS;
    }
}