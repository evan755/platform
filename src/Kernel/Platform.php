<?php declare(strict_types=1);

namespace Evan755\Platform\Kernel;

use Composer\Autoload\ClassLoader;
use DirectoryIterator;
use ReflectionClass;
use stdClass;

final class Platform
{
    protected static ?self $instance = null;

    public string $rootDirectory;
    public string $appsDirectory;
    public string $publicDirectory;
    public string $testsDirectory;
    public string $runtime;
    public string $name;
    public string $version;
    public array $apps;

    protected function __construct()
    {
        $reflection = new ReflectionClass(ClassLoader::class);
        $this->rootDirectory = dirname($reflection->getFileName(), 3);
        $this->appsDirectory = $this->rootDirectory . DIRECTORY_SEPARATOR . 'app';
        $this->publicDirectory = $this->rootDirectory . DIRECTORY_SEPARATOR . 'public';
        $this->testsDirectory = $this->rootDirectory . DIRECTORY_SEPARATOR . 'tests';
        $this->runtime = PHP_SAPI === 'cli' ? 'cli' : 'web';
        $this->name = $this->composer()['name'] ?? 'Platform';
        $this->version = $this->composer()['version'] ?? '1.0.0';
        $this->apps = $this->discoverApps();
    }

    protected function composer(): array
    {
        $file = $this->appsDirectory . DIRECTORY_SEPARATOR . 'composer.json';
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }
        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }
        if (!json_validate($content)) {
            return [];
        }
        return json_decode($content, true);
    }

    protected function discoverApps(): array
    {
        $apps = [];
        is_dir($this->appsDirectory) or mkdir($this->appsDirectory, 0755, true);
        foreach (new DirectoryIterator($this->appsDirectory) as $app) {
            if ($app->isDot()) {
                continue;
            }
            $file = $app->getPathname() . DIRECTORY_SEPARATOR . 'App.json';
            $config = file_exists($file) ? json_decode(file_get_contents($file), false) : null;

            if ($config === null) {
                $config = new stdClass();
            }

            $config->slug = strtolower($app->getFilename());
            $config->appDirectory = $app->getPathname();
            $apps[$app->getFilename()] = $config;
        }

        return $apps;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    public function __wakeup(): void
    {
    }

    private function __clone(): void
    {
    }
}