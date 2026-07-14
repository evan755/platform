<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ViewRepository extends Repository
{
    public function index(string $app): array
    {
        $directory = $this->viewDirectory($app);
        if (!is_dir($directory)) {
            return [];
        }
        $views = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = ltrim(str_replace($directory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $views[] = preg_replace('/\.blade\.php$/', '', str_replace(DIRECTORY_SEPARATOR, '/', $relative));
        }
        return $views;
    }

    public function create(string $app, string $view): bool
    {
        [$path, $parts, $name] = $this->view($app, $view);
        is_dir(dirname($path)) or mkdir(dirname($path), 0755, true);

        return (bool)file_put_contents($path, $this->render($this->stub(), [
            'app' => $app,
        ]));
    }

    protected function view(string $app, string $view): array
    {
        $parts = explode('/', $view);
        $name = array_pop($parts);
        $path = $this->viewDirectory($app);
        foreach ($parts as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
        }
        $path .= DIRECTORY_SEPARATOR . strtolower($name) . '.blade.php';
        return [$path, $parts, $name];
    }

    protected function stub(): string
    {
        return <<<'EOF'
        <div class="platform app-{{$app}}">

        </div>
        EOF;
    }

    public function delete(string $app, string $view): bool
    {
        [$path] = $this->view($app, $view);
        if (!file_exists($path)) {
            return false;
        }
        return unlink($path);
    }

    public function exists(string $app, string $view): bool
    {
        [$path] = $this->view($app, $view);
        return file_exists($path);
    }
}