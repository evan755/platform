<?php declare(strict_types=1);

namespace Tests\Kernel\Repositories;

use Composer\Autoload\ClassLoader;
use Evan755\Platform\Kernel\Platform;
use Evan755\Platform\Kernel\Repositories\AppRepository;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use UnexpectedValueException;

#[CoversClass(AppRepository::class)]
class AppRepositoryTest extends TestCase
{
    protected string $appsDir;
    protected AppRepository $repository;

    public function testAppRepositoryExtendsRepository(): void
    {
        $this->assertInstanceOf('Evan755\Platform\Kernel\Repository', $this->repository);
    }

    public function testIndexMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'index'));
    }

    public function testIndexMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('index');

        $this->assertTrue($method->isPublic());
    }

    // --- 结构测试 ---

    public function testIndexMethodAcceptsNoParameters(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('index');

        $this->assertCount(0, $method->getParameters());
    }

    // --- index 方法测试 ---

    public function testIndexReturnsPlatformApps(): void
    {
        $this->createAppDirectory('test-app');
        $repository = new AppRepository();
        $result = $repository->index();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test-app', $result);
    }

    protected function createAppDirectory(string $name): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . $name;
        mkdir($appDir, 0755, true);
        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', json_encode([
            'name' => $name,
            'database' => ['uri' => '', 'name' => $name . '_db'],
        ]));
        Platform::reset();
    }

    public function testExistsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'exists'));
    }

    public function testExistsMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertTrue($method->isPublic());
    }

    // --- exists 方法测试 ---

    public function testExistsMethodAcceptsOneParameter(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
    }

    public function testExistsReturnsTrueWhenAppExists(): void
    {
        $this->createAppDirectory('test-app');
        $repository = new AppRepository();

        $this->assertTrue($repository->exists('test-app'));
    }

    public function testExistsReturnsFalseWhenAppDoesNotExist(): void
    {
        $this->assertFalse($this->repository->exists('nonexistent'));
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'create'));
    }

    public function testCreateMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertTrue($method->isPublic());
    }

    // --- create 方法测试 ---

    public function testCreateMethodAcceptsOneParameter(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
    }

    public function testCreateReturnsTrueOnSuccess(): void
    {
        $result = $this->repository->create('test-app');

        $this->assertTrue($result);
    }

    public function testCreateCreatesAppDirectory(): void
    {
        $this->repository->create('test-app');
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app';

        $this->assertDirectoryExists($appDir);
    }

    public function testCreateCreatesModelsDirectory(): void
    {
        $this->repository->create('test-app');
        $modelsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models';

        $this->assertDirectoryExists($modelsDir);
    }

    public function testCreateCreatesControllersDirectory(): void
    {
        $this->repository->create('test-app');
        $controllersDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers';

        $this->assertDirectoryExists($controllersDir);
    }

    public function testCreateCreatesViewsDirectory(): void
    {
        $this->repository->create('test-app');
        $viewsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Views';

        $this->assertDirectoryExists($viewsDir);
    }

    public function testCreateCreatesCommandsDirectory(): void
    {
        $this->repository->create('test-app');
        $commandsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands';

        $this->assertDirectoryExists($commandsDir);
    }

    public function testCreateCreatesAppJson(): void
    {
        $this->repository->create('test-app');
        $appJson = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'App.json';

        $this->assertFileExists($appJson);
    }

    public function testCreateAppJsonContainsValidJson(): void
    {
        $this->repository->create('test-app');
        $appJson = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'App.json';
        $content = file_get_contents($appJson);

        $this->assertJson($content);
    }

    public function testCreateAppJsonContainsAppName(): void
    {
        $this->repository->create('test-app');
        $appJson = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'App.json';
        $config = json_decode(file_get_contents($appJson));

        $this->assertSame('test-app', $config->name);
    }

    public function testCreateAppJsonContainsDatabaseConfig(): void
    {
        $this->repository->create('test-app');
        $appJson = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'App.json';
        $config = json_decode(file_get_contents($appJson));

        $this->assertObjectHasProperty('database', $config);
        $this->assertSame('test-app_db', $config->database->name);
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'delete'));
    }

    public function testDeleteMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertTrue($method->isPublic());
    }

    // --- delete 方法测试 ---

    public function testDeleteMethodAcceptsOneParameter(): void
    {
        $reflection = new ReflectionClass(AppRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->createAppDirectory('test-app');
        $repository = new AppRepository();

        $this->assertTrue($repository->delete('test-app'));
    }

    public function testDeleteRemovesAppDirectory(): void
    {
        $this->createAppDirectory('test-app');
        $repository = new AppRepository();
        $repository->delete('test-app');
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app';

        $this->assertDirectoryDoesNotExist($appDir);
    }

    public function testDeleteThrowsExceptionWhenAppDoesNotExist(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->repository->delete('nonexistent');
    }

    protected function setUp(): void
    {
        $rootDir = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        $this->appsDir = $rootDir . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($this->appsDir)) {
            mkdir($this->appsDir, 0755, true);
        }
        Platform::reset();
        $this->repository = new AppRepository();
    }

    protected function tearDown(): void
    {
        Platform::reset();

        if (is_dir($this->appsDir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->appsDir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } else {
                    unlink($item->getPathname());
                }
            }
            rmdir($this->appsDir);
        }
    }
}
