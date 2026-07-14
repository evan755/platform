<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel;

use Doctrine\Inflector\InflectorFactory;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use ReflectionClass;

abstract class Model
{
    protected string $collections = '';
    protected Database $database;
    protected Collection $collection;

    public function __construct(protected string $app)
    {
        $config = $this->config();
        $client = new Client($config['uri']);
        $this->database = $client->selectDatabase($config['name']);
        $this->collection = $this->database->selectCollection($this->resolveCollection());
    }

    protected function config(): array
    {
        $platform = Platform::getInstance();
        if (!array_key_exists($this->app, $platform->apps)) {
            return ['uri' => '', 'name' => $this->app . '_db'];
        }
        $app = $platform->apps[$this->app];
        return [
            'uri' => $app->database->uri ?? '',
            'name' => $app->database->name ?? $this->app . '_db',
        ];
    }

    protected function resolveCollection(): string
    {
        if ($this->collections !== '') {
            return $this->collections;
        }
        $class = new ReflectionClass($this)->getShortName();
        return InflectorFactory::create()->build()->pluralize(strtolower($class));
    }

    public function find(array $filter = [], array $options = []): CursorInterface
    {
        return $this->collection->find($filter, $options);
    }

    public function findOne(array $filter = [], array $options = []): array|object|null
    {
        return $this->collection->findOne($filter, $options);
    }

    public function insert(array|object $document): mixed
    {
        return $this->collection->insertOne($document)->getInsertedId();
    }

    public function update(array $filter, array|object $update, array $options = []): int
    {
        return $this->collection->updateOne($filter, $update, $options)->getModifiedCount();
    }

    public function delete(array $filter): int
    {
        return $this->collection->deleteOne($filter)->getDeletedCount();
    }

    public function count(array $filter = []): int
    {
        return $this->collection->countDocuments($filter);
    }
}
