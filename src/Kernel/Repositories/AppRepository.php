<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AppRepository extends Repository
{
    public function index(): array
    {
        return $this->platform->apps;
    }

    public function create(string $app): bool
    {
        foreach ([$this->appDirectory($app), $this->modelDirectory($app), $this->controllerDirectory($app), $this->viewDirectory($app), $this->commandDirectory($app)] as $directory) {
            is_dir($directory) or mkdir($directory, 0755, true);
        }
        return (
            $this->appConfig($app) &&
            new ModelRepository()->create($app, 'Session') &&
            new ControllerRepository()->create($app, 'Welcome') &&
            new ViewRepository()->create($app, 'home') &&
            new ViewRepository()->create($app, 'about') &&
            new ViewRepository()->create($app, 'help') &&
            new CommandRepository()->create($app, 'Welcome')
        );
    }

    public function delete(string $app): bool
    {
        $directory = $this->appDirectory($app);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            if ($item->isDir()) {
                if (!rmdir($itemPath)) {
                    return false;
                }
            } elseif (!unlink($itemPath)) {
                return false;
            }
        }
        return rmdir($directory);
    }

    public function exists(string $app): bool
    {
        return array_key_exists($app, $this->index());
    }

    protected function appConfig(string $app): bool
    {
        return (bool)file_put_contents($this->appDirectory($app) . DIRECTORY_SEPARATOR . 'App.json', $this->render($this->stub(), [
            'app' => $app,
            'description' => $app . ' Description',
            'version' => '1.0.0',
            'status' => 'enabled',
            'type' => 'application',
            'route_prefix' => strtolower($app),
            'db_uri' => '',
            'db_name' => strtolower($app) . '_db'
        ]));
    }

    protected function stub(): string
    {
        return <<<'EOF'
        {
            "name": "{{ app }}",
            "description": "{{ description }}",
            "version": "{{ version }}",
            "type": "{{ type }}",
            "status": "{{ status }}",
            "route_prefix": "{{ route_prefix }}",
            "database": {
                "uri": "{{ db_uri }}",
                "name": "{{ db_name }}"
            }
        }
        EOF;
    }
}