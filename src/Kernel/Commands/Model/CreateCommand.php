<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\Model;

use Evan755\Platform\Kernel\Repositories\AppRepository;
use Evan755\Platform\Kernel\Repositories\ModelRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('model:create');
        $this->setAliases(['make:model']);
        $this->setDescription('Create a new model in an application');
        $this->addArgument('app', InputArgument::REQUIRED, 'The name of the application');
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the model to create');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $app = (string)$input->getArgument('app');
        $name = (string)$input->getArgument('name');
        $appRepository = new AppRepository();
        $modelRepository = new ModelRepository();

        if (!$appRepository->exists($app)) {
            $output->writeln("<error>Application $app does not exist</error>");
            return Command::FAILURE;
        }

        if ($modelRepository->exists($app, $name)) {
            $output->writeln("<error>Model $name already exists in $app</error>");
            return Command::FAILURE;
        }

        if (!$modelRepository->create($app, $name)) {
            $output->writeln("<error>Failed to create model $name in $app</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Model $name created in $app</info>");
        return Command::SUCCESS;
    }
}
