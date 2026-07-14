<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\Controller;

use Evan755\Platform\Kernel\Repositories\AppRepository;
use Evan755\Platform\Kernel\Repositories\ControllerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('controller:create');
        $this->setAliases(['make:controller']);
        $this->setDescription('Create a new controller in an application');
        $this->addArgument('app', InputArgument::REQUIRED, 'The name of the application');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the controller to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = (string)$input->getArgument('app');
        $name = (string)$input->getArgument('name');
        $appRepository = new AppRepository();
        $controllerRepository = new ControllerRepository();

        if (!$appRepository->exists($app)) {
            $output->writeln("<error>Application $app does not exist</error>");
            return Command::FAILURE;
        }

        if ($controllerRepository->exists($app, $name)) {
            $output->writeln("<error>Controller $name already exists in $app</error>");
            return Command::FAILURE;
        }

        if (!$controllerRepository->create($app, $name)) {
            $output->writeln("<error>Failed to create controller $name in $app</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Controller $name created in $app</info>");
        return Command::SUCCESS;
    }
}
