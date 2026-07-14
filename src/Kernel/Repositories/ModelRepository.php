<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ModelRepository extends Repository
{
    public function index(string $app): array
    {
        $directory = $this->modelDirectory($app);
        if (!is_dir($directory)) {
            return [];
        }
        $models = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }
            $relative = ltrim(str_replace($directory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $models[] = str_replace(DIRECTORY_SEPARATOR, '/', preg_replace('/\.php$/', '', $relative));
        }
        return $models;
    }

    public function create(string $app, string $model): bool
    {
        $path = $this->model($app, $model);
        $dir = dirname($path);
        is_dir($dir) or mkdir($dir, 0755, true);

        $parts = explode('/', $model);
        $class = array_pop($parts);
        $namespace = 'App\\' . $app . '\\Models';
        if (!empty($parts)) {
            $namespace .= '\\' . implode('\\', $parts);
        }

        return (bool)file_put_contents($path, $this->render($this->stub(), [
            'namespace' => $namespace,
            'class' => $class,
        ]));
    }

    protected function model(string $app, string $model): string
    {
        $parts = explode('/', $model);
        $name = array_pop($parts);
        $path = $this->modelDirectory($app);
        foreach ($parts as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
        }
        return $path . DIRECTORY_SEPARATOR . $name . '.php';
    }

    protected function stub(): string
    {
        return <<<'EOF'
        <?php declare(strict_types=1);

        namespace {{ namespace }};

        use Evan755\Platform\Kernel\Model;

        class {{ class }} extends Model
        {

        }
        EOF;
    }

    public function delete(string $app, string $model): bool
    {
        $path = $this->model($app, $model);
        if (!file_exists($path)) {
            return false;
        }
        return unlink($path);
    }

    public function exists(string $app, string $model): bool
    {
        return file_exists($this->model($app, $model));
    }
}
