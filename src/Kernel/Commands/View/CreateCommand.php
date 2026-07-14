<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\View;

use Evan755\Platform\Kernel\Repositories\AppRepository;
use Evan755\Platform\Kernel\Repositories\ViewRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('view:create');
        $this->setAliases(['make:view']);
        $this->setDescription('Create a new view in an application');
        $this->addArgument('app', InputArgument::REQUIRED, 'The name of the application');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the view to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = (string)$input->getArgument('app');
        $name = (string)$input->getArgument('name');
        $appRepository = new AppRepository();
        $viewRepository = new ViewRepository();

        if (!$appRepository->exists($app)) {
            $output->writeln("<error>Application $app does not exist</error>");
            return Command::FAILURE;
        }

        if ($viewRepository->exists($app, $name)) {
            $output->writeln("<error>View $name already exists in $app</error>");
            return Command::FAILURE;
        }

        if (!$viewRepository->create($app, $name)) {
            $output->writeln("<error>Failed to create view $name in $app</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>View $name created in $app</info>");
        return Command::SUCCESS;
    }
}
