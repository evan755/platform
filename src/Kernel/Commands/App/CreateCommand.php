<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\App;

use Evan755\Platform\Kernel\Repositories\AppRepository;
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
        $app = $input->getArgument('app');
        $appRepository = new AppRepository();
        if ($appRepository->exists($app)) {
            $output->writeln("<error>Application $app already exists</error>");
            return Command::FAILURE;
        }
        if (!$appRepository->create($app)) {
            $output->writeln("<error>Application $app already exists</error>");
            return Command::FAILURE;
        }
        $output->writeln("<info>Application $app created</info>");
        return Command::SUCCESS;
    }
}