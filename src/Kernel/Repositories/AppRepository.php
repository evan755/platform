<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel\Repositories;

use Evan755\Platform\Kernel\Repository;

class AppRepository extends Repository
{
    public function index(): array
    {
        return $this->platform->apps;
    }

    public function create(string $app): bool
    {
        return true;
    }

    public function delete(string $app): bool
    {
        return true;
    }

    public function exists(string $app): bool
    {
        return array_key_exists($app, $this->index());
    }

    public function enable(string $app): bool
    {
        return true;
    }

    public function disable(string $app): bool
    {
        return true;
    }
}