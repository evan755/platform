<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ControllerRepository extends Repository
{
    public function index(string $app): array
    {
        $directory = $this->controllerDirectory($app);
        if (!is_dir($directory)) {
            return [];
        }
        $controllers = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = ltrim(str_replace($directory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $controllers[] = preg_replace('/Controller\.php$/', '', str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }
        return $controllers;
    }

    public function create(string $app, string $controller): bool
    {
        [$path, $parts, $name] = $this->controller($app, $controller);
        is_dir(dirname($path)) or mkdir(dirname($path), 0755, true);

        $namespace = 'App\\' . $app . '\\Controllers';
        if (!empty($parts)) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        return (bool)file_put_contents($path, $this->render($this->stub(), [
            'namespace' => $namespace,
            'class' => $name . 'Controller',
        ]));
    }

    protected function controller(string $app, string $controller): array
    {
        $parts = explode('/', $controller);
        $name = array_pop($parts);
        $path = $this->controllerDirectory($app);
        foreach ($parts as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
        }
        $path .= DIRECTORY_SEPARATOR . $name . 'Controller.php';
        return [$path, $parts, $name];
    }

    protected function stub(): string
    {
        return <<<'EOF'
        <?php declare(strict_types=1);

        namespace {{ namespace }};

        use Evan755\Platform\Kernel\Controller;

        class {{ class }} extends Controller
        {

        }
        EOF;
    }

    public function delete(string $app, string $controller): bool
    {
        [$path] = $this->controller($app, $controller);
        if (!file_exists($path)) {
            return false;
        }
        return unlink($path);
    }

    public function exists(string $app, string $controller): bool
    {
        [$path] = $this->controller($app, $controller);
        return file_exists($path);
    }
}