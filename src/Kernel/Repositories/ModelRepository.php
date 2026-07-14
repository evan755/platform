<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use DirectoryIterator;
use Evan755\Platform\Kernel\Repository;

class ModelRepository extends Repository
{
    public function index(string $app): array
    {
        $directory = $this->modelDirectory($app);
        if (!is_dir($directory)) {
            return [];
        }
        $models = [];
        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            if ($file->getExtension() === 'php') {
                $models[] = $file->getBasename('.php');
            }
        }
        return $models;
    }

    public function create(string $app, string $model): bool
    {
        $path = $this->model($app, $model);
        $dir = dirname($path);
        is_dir($dir) or mkdir($dir, 0755, true);
        return (bool)file_put_contents($path, $this->render($this->stub(), [
            'namespace' => 'App\\' . $app . '\\Models',
            'class' => $model,
        ]));
    }

    public function model(string $app, string $model): string
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
