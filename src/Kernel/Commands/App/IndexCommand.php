<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Commands\App;

use Evan755\Platform\Kernel\Repositories\AppRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
        $table = new Table($output);
        $table->setHeaderTitle($this->apps()->title)->setHeaders($this->apps()->headers)->setRows($this->apps()->rows)->setFooterTitle($this->apps()->footers)->render();
        return Command::SUCCESS;
    }

    protected function apps(): object
    {
        $rows = [];
        foreach (new AppRepository()->index() as $app => $config) {
            $rows[] = [$app, $config->version, $config->type, $config->status, $config->description];
        }
        $apps = ['title' => 'Applications', 'headers' => ['Name', 'Version', 'Type', 'Status', 'Description'], 'rows' => $rows, 'footers' => (string)count($rows)];
        return (object)$apps;
    }
}