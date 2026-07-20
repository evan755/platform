<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel;

use Doctrine\Inflector\InflectorFactory;
use MongoDB\BSON\UTCDateTime;
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

    protected function now(): UTCDateTime
    {
        return new UTCDateTime();
    }

    public function find(array $filter = [], array $options = []): CursorInterface
    {
        $filter['deleted_at'] = ['$exists' => false];
        return $this->collection->find($filter, $options);
    }

    public function findOne(array $filter = [], array $options = []): array|object|null
    {
        $filter['deleted_at'] = ['$exists' => false];
        return $this->collection->findOne($filter, $options);
    }

    public function insert(array|object $document): mixed
    {
        if (is_array($document)) {
            $document['created_at'] = $this->now();
            $document['updated_at'] = $this->now();
        }
        return $this->collection->insertOne($document)->getInsertedId();
    }

    public function update(array $filter, array|object $update, array $options = []): int
    {
        if (is_array($update) && !empty($update) && !str_starts_with(array_key_first($update) ?? '', '$')) {
            $update = ['$set' => $update];
        }
        if (isset($update['$set'])) {
            $update['$set']['updated_at'] = $this->now();
        } else {
            $update['$set'] = ['updated_at' => $this->now()];
        }
        return $this->collection->updateOne($filter, $update, $options)->getModifiedCount();
    }

    public function delete(array $filter): int
    {
        return $this->collection->deleteOne($filter)->getDeletedCount();
    }

    public function softDelete(array $filter): int
    {
        return $this->collection->updateOne($filter, [
            '$set' => ['deleted_at' => $this->now()],
        ])->getModifiedCount();
    }

    public function count(array $filter = []): int
    {
        $filter['deleted_at'] = ['$exists' => false];
        return $this->collection->countDocuments($filter);
    }

    public function withTrashed(): array
    {
        return [];
    }

    public function onlyTrashed(array $filter = [], array $options = []): CursorInterface
    {
        $filter['deleted_at'] = ['$exists' => true];
        return $this->collection->find($filter, $options);
    }
}