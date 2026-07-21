<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel;

use Doctrine\Inflector\InflectorFactory;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
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
        $this->collection = $this->database->selectCollection($this->collection());
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

    protected function collection(): string
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

}