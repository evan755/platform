<?php declare(strict_types=1);

namespace Tests\Kernel\Repositories;

use Composer\Autoload\ClassLoader;
use Evan755\Platform\Kernel\Platform;
use Evan755\Platform\Kernel\Repositories\ViewRepository;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

#[CoversClass(ViewRepository::class)]
class ViewRepositoryTest extends TestCase
{
    protected string $appsDir;
    protected ViewRepository $repository;

    public function testViewRepositoryExtendsRepository(): void
    {
        $this->assertInstanceOf('Evan755\Platform\Kernel\Repository', $this->repository);
    }

    public function testIndexMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'index'));
    }

    public function testIndexMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
        $method = $reflection->getMethod('index');

        $this->assertTrue($method->isPublic());
    }

    // --- 结构测试 ---

    public function testIndexMethodAcceptsOneParameter(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
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

    public function testIndexReturnsEmptyArrayWhenNoViews(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();

        $this->assertSame([], $repository->index('test-app'));
    }

    protected function createAppWithViews(string $app, array $views = []): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . $app;
        $viewsDir = $appDir . DIRECTORY_SEPARATOR . 'Views';
        mkdir($viewsDir, 0755, true);

        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', json_encode([
            'name' => $app,
            'database' => ['uri' => '', 'name' => $app . '_db'],
        ]));

        foreach ($views as $view) {
            file_put_contents($viewsDir . DIRECTORY_SEPARATOR . strtolower($view) . '.blade.php', '<div></div>');
        }

        Platform::reset();
    }

    public function testIndexReturnsViews(): void
    {
        $this->createAppWithViews('test-app', ['home', 'about']);
        $repository = new ViewRepository();
        $result = $repository->index('test-app');

        $this->assertCount(2, $result);
        $this->assertContains('home', $result);
        $this->assertContains('about', $result);
    }

    public function testIndexIncludesAllPhpFiles(): void
    {
        $this->createAppWithViews('test-app', ['home']);
        $viewsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Views';
        file_put_contents($viewsDir . DIRECTORY_SEPARATOR . 'helper.blade.php', '<div>helper</div>');

        $repository = new ViewRepository();
        $result = $repository->index('test-app');

        // ViewRepository 会扫描所有 .php 文件
        $this->assertCount(2, $result);
        $this->assertContains('home', $result);
        $this->assertContains('helper', $result);
    }

    public function testIndexStripsBladePhpSuffix(): void
    {
        $this->createAppWithViews('test-app', ['home']);
        $repository = new ViewRepository();
        $result = $repository->index('test-app');

        $this->assertContains('home', $result);
        $this->assertNotContains('home.blade', $result);
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'create'));
    }

    public function testCreateMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertTrue($method->isPublic());
    }

    // --- create 方法测试 ---

    public function testCreateMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('view', $method->getParameters()[1]->getName());
    }

    public function testCreateReturnsTrueOnSuccess(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();

        $this->assertTrue($repository->create('test-app', 'dashboard'));
    }

    public function testCreateCreatesViewFile(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();
        $repository->create('test-app', 'dashboard');

        $viewFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'dashboard.blade.php';
        $this->assertFileExists($viewFile);
    }

    public function testCreateViewFileContainsBladeContent(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();
        $repository->create('test-app', 'dashboard');

        $viewFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'dashboard.blade.php';
        $content = file_get_contents($viewFile);

        $this->assertStringContainsString('<div', $content);
        $this->assertStringContainsString('test-app', $content);
    }

    public function testCreateCreatesNestedViewFile(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();
        $repository->create('test-app', 'admin/dashboard');

        $viewFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'dashboard.blade.php';
        $this->assertFileExists($viewFile);
    }

    public function testCreateNestedViewFileContainsCorrectContent(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();
        $repository->create('test-app', 'admin/dashboard');

        $viewFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'dashboard.blade.php';
        $content = file_get_contents($viewFile);

        $this->assertStringContainsString('test-app', $content);
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'delete'));
    }

    public function testDeleteMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertTrue($method->isPublic());
    }

    // --- delete 方法测试 ---

    public function testDeleteMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('view', $method->getParameters()[1]->getName());
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->createAppWithViews('test-app', ['home']);
        $repository = new ViewRepository();

        $this->assertTrue($repository->delete('test-app', 'home'));
    }

    public function testDeleteRemovesViewFile(): void
    {
        $this->createAppWithViews('test-app', ['home']);
        $repository = new ViewRepository();
        $repository->delete('test-app', 'home');

        $viewFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'home.blade.php';
        $this->assertFileDoesNotExist($viewFile);
    }

    public function testDeleteReturnsFalseWhenViewDoesNotExist(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();

        $this->assertFalse($repository->delete('test-app', 'nonexistent'));
    }

    public function testExistsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'exists'));
    }

    public function testExistsMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertTrue($method->isPublic());
    }

    // --- exists 方法测试 ---

    public function testExistsMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ViewRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('view', $method->getParameters()[1]->getName());
    }

    public function testExistsReturnsTrueWhenViewExists(): void
    {
        $this->createAppWithViews('test-app', ['home']);
        $repository = new ViewRepository();

        $this->assertTrue($repository->exists('test-app', 'home'));
    }

    public function testExistsReturnsFalseWhenViewDoesNotExist(): void
    {
        $this->createAppWithViews('test-app');
        $repository = new ViewRepository();

        $this->assertFalse($repository->exists('test-app', 'nonexistent'));
    }

    protected function setUp(): void
    {
        $rootDir = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        $this->appsDir = $rootDir . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($this->appsDir)) {
            mkdir($this->appsDir, 0755, true);
        }
        Platform::reset();
        $this->repository = new ViewRepository();
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
