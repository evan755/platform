<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CommandRepository extends Repository
{
    public function index(string $app): array
    {
        $directory = $this->commandDirectory($app);
        if (!is_dir($directory)) {
            return [];
        }
        $commands = [];
        $iterator = new \RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = ltrim(str_replace($directory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $commands[] = preg_replace('/Command\.php$/', '', str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }
        return $commands;
    }

    public function create(string $app, string $command): bool
    {
        [$path, $parts, $name] = $this->command($app, $command);
        is_dir(dirname($path)) or mkdir(dirname($path), 0755, true);

        $namespace = 'App\\' . $app . '\\Commands';
        if (!empty($parts)) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        return (bool)file_put_contents($path, $this->render($this->stub(), [
            'namespace' => $namespace,
            'class' => $name . 'Command',
            'command' => strtolower($app) . ':' . strtolower(str_replace('/', ':', $command)),
        ]));
    }

    public function delete(string $app, string $command): bool
    {
        [$path] = $this->command($app, $command);
        if (!file_exists($path)) {
            return false;
        }
        return unlink($path);
    }

    public function exists(string $app, string $command): bool
    {
        [$path] = $this->command($app, $command);
        return file_exists($path);
    }

    protected function command(string $app, string $command): array
    {
        $parts = explode('/', $command);
        $name = array_pop($parts);
        $path = $this->commandDirectory($app);
        foreach ($parts as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
        }
        $path .= DIRECTORY_SEPARATOR . $name . 'Command.php';
        return [$path, $parts, $name];
    }

    protected function stub(): string
    {
        return <<<'EOF'
        <?php declare(strict_types=1);
        
        namespace {{ namespace }};
        
        use Symfony\Component\Console\Command\Command;
        use Symfony\Component\Console\Input\InputArgument;
        use Symfony\Component\Console\Input\InputInterface;
        use Symfony\Component\Console\Output\OutputInterface;
        
        class {{ class }} extends Command
        {
            protected function configure(): void
            {
                 $this->setName('{{ command }}');
            }
            
            protected function execute(InputInterface $input, OutputInterface $output): int
            {
                return Command::SUCCESS;
            }
        }
        EOF;
    }
}