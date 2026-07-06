<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;

class ViewRepository extends Repository
{
    public function index(string $app): array
    {
        return [];
    }

    public function create(string $app, string $view): bool
    {
        return true;
    }

    public function delete(string $app, string $view): bool
    {
        return true;
    }

    public function exists(string $app, string $view): bool
    {
        return file_exists($this->view($app, $view));
    }

    protected function view(string $app, string $view): string
    {
        $parts = explode('/', $view);
        $name = array_pop($parts);
        $path = $this->viewDirectory($app);
        foreach ($parts as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
        }
        $path .= DIRECTORY_SEPARATOR . $name . '.blade.php';
        $dir = dirname($path);
        $file = basename($path);
        return $dir . DIRECTORY_SEPARATOR . strtolower($file);
    }

    protected function stub(): string
    {
        return <<<'EOF'
        <div class="platform app-{{$app}}">
            
        </div>
        EOF;
    }
}