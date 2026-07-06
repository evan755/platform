<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;

class ModelRepository extends Repository
{
    public function index(string $app): array
    {
        return [];
    }

    public function create(string $app, string $model): bool
    {
        return true;
    }

    public function delete(string $app, string $model): bool
    {
        return true;
    }

    public function exists(string $app, string $model): bool
    {
        return file_exists($this->model($app, $model));
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
}