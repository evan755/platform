<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;

class CommandRepository extends Repository
{
    public function index(string $app): array
    {
        return [];
    }

    public function create(string $app, string $command): bool
    {
        return true;
    }

    public function delete(string $app, string $command): bool
    {
        return true;
    }

    public function exists(string $app, string $command): bool
    {
        return file_exists($this->command($app, $command));
    }

    protected function command(string $app, string $command): string
    {
        $parts = explode('/', $command);
        $name = array_pop($parts);
        $path = $this->commandDirectory($app);
        foreach ($parts as $dir) {
            $path .= DIRECTORY_SEPARATOR . $dir;
        }
        return $path . DIRECTORY_SEPARATOR . $name . 'Command.php';
    }
}