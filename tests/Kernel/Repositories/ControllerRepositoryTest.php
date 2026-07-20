<?php declare(strict_types=1);

namespace Tests\Kernel\Repositories;

use Composer\Autoload\ClassLoader;
use Evan755\Platform\Kernel\Platform;
use Evan755\Platform\Kernel\Repositories\ControllerRepository;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

#[CoversClass(ControllerRepository::class)]
class ControllerRepositoryTest extends TestCase
{
    protected string $appsDir;
    protected ControllerRepository $repository;

    public function testControllerRepositoryExtendsRepository(): void
    {
        $this->assertInstanceOf('Evan755\Platform\Kernel\Repository', $this->repository);
    }

    public function testIndexMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'index'));
    }

    public function testIndexMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('index');

        $this->assertTrue($method->isPublic());
    }

    // --- 结构测试 ---

    public function testIndexMethodAcceptsOneParameter(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('index');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
    }

    // --- index 方法测试 ---

    public function testIndexReturnsEmptyArrayWhenDirectoryDoesNotExist(): void
    {
        $result = $this->repository->index('nonexistent');

        $this->assertSame([], $result);
    }

    public function testIndexReturnsEmptyArrayWhenNoControllers(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();

        $this->assertSame([], $repository->index('test-app'));
    }

    protected function createAppWithControllers(string $app, array $controllers = []): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . $app;
        $controllersDir = $appDir . DIRECTORY_SEPARATOR . 'Controllers';
        mkdir($controllersDir, 0755, true);

        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', json_encode([
            'name' => $app,
            'database' => ['uri' => '', 'name' => $app . '_db'],
        ]));

        foreach ($controllers as $controller) {
            file_put_contents($controllersDir . DIRECTORY_SEPARATOR . $controller . 'Controller.php', '<?php');
        }

        Platform::reset();
    }

    public function testIndexReturnsControllers(): void
    {
        $this->createAppWithControllers('test-app', ['Welcome', 'Dashboard']);
        $repository = new ControllerRepository();
        $result = $repository->index('test-app');

        $this->assertCount(2, $result);
        $this->assertContains('Welcome', $result);
        $this->assertContains('Dashboard', $result);
    }

    public function testIndexSkipsNonPhpFiles(): void
    {
        $this->createAppWithControllers('test-app', ['Welcome']);
        $controllersDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers';
        file_put_contents($controllersDir . DIRECTORY_SEPARATOR . 'README.md', '# Controllers');

        $repository = new ControllerRepository();
        $result = $repository->index('test-app');

        $this->assertCount(1, $result);
        $this->assertContains('Welcome', $result);
    }

    public function testIndexStripsControllerSuffix(): void
    {
        $this->createAppWithControllers('test-app', ['Welcome']);
        $repository = new ControllerRepository();
        $result = $repository->index('test-app');

        $this->assertContains('Welcome', $result);
        $this->assertNotContains('WelcomeController', $result);
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'create'));
    }

    public function testCreateMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertTrue($method->isPublic());
    }

    // --- create 方法测试 ---

    public function testCreateMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('controller', $method->getParameters()[1]->getName());
    }

    public function testCreateReturnsTrueOnSuccess(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();

        $this->assertTrue($repository->create('test-app', 'Dashboard'));
    }

    public function testCreateCreatesControllerFile(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();
        $repository->create('test-app', 'Dashboard');

        $controllerFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'DashboardController.php';
        $this->assertFileExists($controllerFile);
    }

    public function testCreateControllerFileContainsValidPhp(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();
        $repository->create('test-app', 'Dashboard');

        $controllerFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'DashboardController.php';
        $content = file_get_contents($controllerFile);

        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('class DashboardController extends Controller', $content);
    }

    public function testCreateControllerFileContainsCorrectNamespace(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();
        $repository->create('test-app', 'Dashboard');

        $controllerFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'DashboardController.php';
        $content = file_get_contents($controllerFile);

        $this->assertStringContainsString('namespace App\\test-app\\Controllers;', $content);
    }

    public function testCreateCreatesNestedControllerFile(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();
        $repository->create('test-app', 'Admin/Dashboard');

        $controllerFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'DashboardController.php';
        $this->assertFileExists($controllerFile);
    }

    public function testCreateNestedControllerFileContainsCorrectNamespace(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();
        $repository->create('test-app', 'Admin/Dashboard');

        $controllerFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'DashboardController.php';
        $content = file_get_contents($controllerFile);

        $this->assertStringContainsString('namespace App\\test-app\\Controllers\\Admin;', $content);
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'delete'));
    }

    public function testDeleteMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertTrue($method->isPublic());
    }

    // --- delete 方法测试 ---

    public function testDeleteMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('controller', $method->getParameters()[1]->getName());
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->createAppWithControllers('test-app', ['Welcome']);
        $repository = new ControllerRepository();

        $this->assertTrue($repository->delete('test-app', 'Welcome'));
    }

    public function testDeleteRemovesControllerFile(): void
    {
        $this->createAppWithControllers('test-app', ['Welcome']);
        $repository = new ControllerRepository();
        $repository->delete('test-app', 'Welcome');

        $controllerFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'WelcomeController.php';
        $this->assertFileDoesNotExist($controllerFile);
    }

    public function testDeleteReturnsFalseWhenControllerDoesNotExist(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();

        $this->assertFalse($repository->delete('test-app', 'Nonexistent'));
    }

    public function testExistsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'exists'));
    }

    public function testExistsMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertTrue($method->isPublic());
    }

    // --- exists 方法测试 ---

    public function testExistsMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ControllerRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('controller', $method->getParameters()[1]->getName());
    }

    public function testExistsReturnsTrueWhenControllerExists(): void
    {
        $this->createAppWithControllers('test-app', ['Welcome']);
        $repository = new ControllerRepository();

        $this->assertTrue($repository->exists('test-app', 'Welcome'));
    }

    public function testExistsReturnsFalseWhenControllerDoesNotExist(): void
    {
        $this->createAppWithControllers('test-app');
        $repository = new ControllerRepository();

        $this->assertFalse($repository->exists('test-app', 'Nonexistent'));
    }

    protected function setUp(): void
    {
        $rootDir = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        $this->appsDir = $rootDir . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($this->appsDir)) {
            mkdir($this->appsDir, 0755, true);
        }
        Platform::reset();
        $this->repository = new ControllerRepository();
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
