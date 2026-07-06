<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel;

abstract class Repository
{
    protected Platform $platform;

    public function __construct()
    {
        $this->platform = Platform::getInstance();
    }

    protected function commandDirectory(string $name): string
    {
        return $this->appDirectory($name) . DIRECTORY_SEPARATOR . 'Commands';
    }

    protected function appDirectory(string $app): string
    {
        return $this->platform->appsDirectory . DIRECTORY_SEPARATOR . $app;
    }

    protected function controllerDirectory(string $name): string
    {
        return $this->appDirectory($name) . DIRECTORY_SEPARATOR . 'Controllers';
    }

    protected function modelDirectory(string $name): string
    {
        return $this->appDirectory($name) . DIRECTORY_SEPARATOR . 'Models';
    }

    protected function viewDirectory(string $name): string
    {
        return $this->appDirectory($name) . DIRECTORY_SEPARATOR . 'Views';
    }

    protected function render(string $stub, array $vars): string
    {
        return str_replace(
            array_map(static fn(string $key): string => '{{ ' . $key . ' }}', array_keys($vars)),
            array_values($vars),
            $stub
        );
    }
}