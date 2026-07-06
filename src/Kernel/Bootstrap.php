<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel;

use Composer\InstalledVersions;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;

class Bootstrap
{
    protected Platform $platform;

    public function __construct()
    {
        $this->platform = Platform::getInstance();
    }

    public function Run(): void
    {
        match ($this->platform->runtime) {
            'web' => $this->web(),
            'cli' => $this->cli()
        };
    }

    protected function web(): void
    {
        var_dump($this->platform);
    }

    protected function cli(): void
    {
        $application = new Application($this->platform->name, $this->platform->version);
        $application->addCommands($this->commands());
        $application->run();
    }

    protected function commands(): array
    {
        $commands = $this->kernelCommands();
        foreach ($this->platform->apps as $app) {
            $directory = $app->appDirectory . DIRECTORY_SEPARATOR . 'Commands';
            if (!is_dir($directory)) {
                continue;
            }
            $commands = array_merge($commands, $this->command($directory, $app->slug));
        }
        return $commands;
    }

    protected function kernelCommands(): array
    {
        $directory = InstalledVersions::getInstallPath('evan755/platform') . '/src/Kernel/Commands';
        if (!is_dir($directory)) {
            return [];
        }
        $commands = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isDir() || !str_ends_with($file->getFilename(), 'Command.php')) {
                continue;
            }
            $relative = str_replace($directory, '', $file->getPathname());
            $class = 'Evan755\\Platform\\Kernel\\Commands\\' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], ltrim($relative, DIRECTORY_SEPARATOR));
            $reflection = new ReflectionClass($class);
            if ($reflection->isSubclassOf(Command::class) && !$reflection->isAbstract()) {
                $commands[] = new $class();
            }
        }
        return $commands;
    }

    protected function command(string $directory, string $name): array
    {
        $commands = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isDir() || !str_ends_with($file->getFilename(), 'Command.php')) {
                continue;
            }
            $relative = str_replace($directory, '', $file->getPathname());
            $qualified = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
            $class = "App\\" . $name . "\\Commands\\" . ltrim($qualified, '\\');
            if (!class_exists($class)) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            if ($reflection->isSubclassOf(Command::class) && !$reflection->isAbstract()) {
                $commands[] = new $class();
            }
        }
        return $commands;
    }
}