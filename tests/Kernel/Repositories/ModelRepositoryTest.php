<?php declare(strict_types=1);

namespace Tests\Kernel\Repositories;

use Composer\Autoload\ClassLoader;
use Evan755\Platform\Kernel\Platform;
use Evan755\Platform\Kernel\Repositories\ModelRepository;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

#[CoversClass(ModelRepository::class)]
class ModelRepositoryTest extends TestCase
{
    protected string $appsDir;
    protected ModelRepository $repository;

    public function testModelRepositoryExtendsRepository(): void
    {
        $this->assertInstanceOf('Evan755\Platform\Kernel\Repository', $this->repository);
    }

    public function testIndexMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'index'));
    }

    public function testIndexMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
        $method = $reflection->getMethod('index');

        $this->assertTrue($method->isPublic());
    }

    // --- 结构测试 ---

    public function testIndexMethodAcceptsOneParameter(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
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

    public function testIndexReturnsEmptyArrayWhenNoModels(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();

        $this->assertSame([], $repository->index('test-app'));
    }

    protected function createAppWithModels(string $app, array $models = []): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . $app;
        $modelsDir = $appDir . DIRECTORY_SEPARATOR . 'Models';
        mkdir($modelsDir, 0755, true);

        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', json_encode([
            'name' => $app,
            'database' => ['uri' => '', 'name' => $app . '_db'],
        ]));

        foreach ($models as $model) {
            file_put_contents($modelsDir . DIRECTORY_SEPARATOR . $model . '.php', '<?php');
        }

        Platform::reset();
    }

    public function testIndexReturnsModels(): void
    {
        $this->createAppWithModels('test-app', ['Session', 'User']);
        $repository = new ModelRepository();
        $result = $repository->index('test-app');

        $this->assertCount(2, $result);
        $this->assertContains('Session', $result);
        $this->assertContains('User', $result);
    }

    public function testIndexSkipsNonPhpFiles(): void
    {
        $this->createAppWithModels('test-app', ['Session']);
        $modelsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models';
        file_put_contents($modelsDir . DIRECTORY_SEPARATOR . 'README.md', '# Models');

        $repository = new ModelRepository();
        $result = $repository->index('test-app');

        $this->assertCount(1, $result);
        $this->assertContains('Session', $result);
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'create'));
    }

    public function testCreateMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertTrue($method->isPublic());
    }

    // --- create 方法测试 ---

    public function testCreateMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('model', $method->getParameters()[1]->getName());
    }

    public function testCreateReturnsTrueOnSuccess(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();

        $this->assertTrue($repository->create('test-app', 'User'));
    }

    public function testCreateCreatesModelFile(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();
        $repository->create('test-app', 'User');

        $modelFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'User.php';
        $this->assertFileExists($modelFile);
    }

    public function testCreateModelFileContainsValidPhp(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();
        $repository->create('test-app', 'User');

        $modelFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'User.php';
        $content = file_get_contents($modelFile);

        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('class User extends Model', $content);
    }

    public function testCreateModelFileContainsCorrectNamespace(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();
        $repository->create('test-app', 'User');

        $modelFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'User.php';
        $content = file_get_contents($modelFile);

        $this->assertStringContainsString('namespace App\\test-app\\Models;', $content);
    }

    public function testCreateCreatesNestedModelFile(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();
        $repository->create('test-app', 'Admin/User');

        $modelFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'User.php';
        $this->assertFileExists($modelFile);
    }

    public function testCreateNestedModelFileContainsCorrectNamespace(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();
        $repository->create('test-app', 'Admin/User');

        $modelFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR . 'User.php';
        $content = file_get_contents($modelFile);

        $this->assertStringContainsString('namespace App\\test-app\\Models\\Admin;', $content);
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'delete'));
    }

    public function testDeleteMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertTrue($method->isPublic());
    }

    // --- delete 方法测试 ---

    public function testDeleteMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('model', $method->getParameters()[1]->getName());
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->createAppWithModels('test-app', ['User']);
        $repository = new ModelRepository();

        $this->assertTrue($repository->delete('test-app', 'User'));
    }

    public function testDeleteRemovesModelFile(): void
    {
        $this->createAppWithModels('test-app', ['User']);
        $repository = new ModelRepository();
        $repository->delete('test-app', 'User');

        $modelFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'User.php';
        $this->assertFileDoesNotExist($modelFile);
    }

    public function testDeleteReturnsFalseWhenModelDoesNotExist(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();

        $this->assertFalse($repository->delete('test-app', 'Nonexistent'));
    }

    public function testExistsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'exists'));
    }

    public function testExistsMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertTrue($method->isPublic());
    }

    // --- exists 方法测试 ---

    public function testExistsMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(ModelRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('model', $method->getParameters()[1]->getName());
    }

    public function testExistsReturnsTrueWhenModelExists(): void
    {
        $this->createAppWithModels('test-app', ['User']);
        $repository = new ModelRepository();

        $this->assertTrue($repository->exists('test-app', 'User'));
    }

    public function testExistsReturnsFalseWhenModelDoesNotExist(): void
    {
        $this->createAppWithModels('test-app');
        $repository = new ModelRepository();

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
        $this->repository = new ModelRepository();
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
