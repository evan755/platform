<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;

class ControllerRepository extends Repository
{
    public function index(string $app): array
    {
        return [];
    }

    public function create(string $app, string $controller): bool
    {
        return true;
    }

    public function delete(string $app, string $controller): bool
    {
        return true;
    }

    public function exists(string $app, string $controller): bool
    {
        return file_exists($this->controller($app, $controller));
    }

    protected function controller(string $app, string $controller): string
    {
        $parts = explode('/', $controller);
        $name = array_pop($parts);
        $path = $this->controllerDirectory($app);
        foreach ($parts as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
        }
        return $path . DIRECTORY_SEPARATOR . $name . 'Controller.php';
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

}