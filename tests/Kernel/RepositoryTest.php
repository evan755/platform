<?php declare(strict_types=1);

namespace Tests\Kernel;

use Evan755\Platform\Kernel\Platform;
use Evan755\Platform\Kernel\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * 用于测试的 Repository 子类
 */
class TestableRepository extends Repository
{
    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function callCommandDirectory(string $name): string
    {
        return $this->commandDirectory($name);
    }

    public function callAppDirectory(string $app): string
    {
        return $this->appDirectory($app);
    }

    public function callControllerDirectory(string $name): string
    {
        return $this->controllerDirectory($name);
    }

    public function callModelDirectory(string $name): string
    {
        return $this->modelDirectory($name);
    }

    public function callViewDirectory(string $name): string
    {
        return $this->viewDirectory($name);
    }

    public function callRender(string $stub, array $vars): string
    {
        return $this->render($stub, $vars);
    }
}

#[CoversClass(Repository::class)]
class RepositoryTest extends TestCase
{
    public function testRepositoryIsAbstract(): void
    {
        $reflection = new ReflectionClass(Repository::class);

        $this->assertTrue($reflection->isAbstract());
    }

    public function testRepositoryHasPlatformProperty(): void
    {
        $reflection = new ReflectionProperty(Repository::class, 'platform');

        $this->assertTrue($reflection->isProtected());
        $this->assertSame('Evan755\Platform\Kernel\Platform', $reflection->getType()->getName());
    }

    // --- 结构测试 ---

    public function testConstructorSetsPlatformProperty(): void
    {
        $repository = new TestableRepository();

        $this->assertInstanceOf(Platform::class, $repository->getPlatform());
    }

    public function testConstructorUsesPlatformSingleton(): void
    {
        $repository = new TestableRepository();
        $platform = Platform::getInstance();

        $this->assertSame($platform, $repository->getPlatform());
    }

    // --- 构造函数测试 ---

    public function testCommandDirectoryMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Repository::class, 'commandDirectory');

        $this->assertTrue($method->isProtected());
    }

    public function testAppDirectoryMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Repository::class, 'appDirectory');

        $this->assertTrue($method->isProtected());
    }

    // --- 方法可见性测试 ---

    public function testControllerDirectoryMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Repository::class, 'controllerDirectory');

        $this->assertTrue($method->isProtected());
    }

    public function testModelDirectoryMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Repository::class, 'modelDirectory');

        $this->assertTrue($method->isProtected());
    }

    public function testViewDirectoryMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Repository::class, 'viewDirectory');

        $this->assertTrue($method->isProtected());
    }

    public function testRenderMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Repository::class, 'render');

        $this->assertTrue($method->isProtected());
    }

    public function testCommandDirectoryMethodAcceptsOneParameter(): void
    {
        $method = new ReflectionMethod(Repository::class, 'commandDirectory');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('name', $method->getParameters()[0]->getName());
    }

    public function testAppDirectoryMethodAcceptsOneParameter(): void
    {
        $method = new ReflectionMethod(Repository::class, 'appDirectory');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
    }

    // --- 方法签名测试 ---

    public function testControllerDirectoryMethodAcceptsOneParameter(): void
    {
        $method = new ReflectionMethod(Repository::class, 'controllerDirectory');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('name', $method->getParameters()[0]->getName());
    }

    public function testModelDirectoryMethodAcceptsOneParameter(): void
    {
        $method = new ReflectionMethod(Repository::class, 'modelDirectory');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('name', $method->getParameters()[0]->getName());
    }

    public function testViewDirectoryMethodAcceptsOneParameter(): void
    {
        $method = new ReflectionMethod(Repository::class, 'viewDirectory');

        $this->assertCount(1, $method->getParameters());
        $this->assertSame('name', $method->getParameters()[0]->getName());
    }

    public function testRenderMethodAcceptsTwoParameters(): void
    {
        $method = new ReflectionMethod(Repository::class, 'render');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('stub', $method->getParameters()[0]->getName());
        $this->assertSame('vars', $method->getParameters()[1]->getName());
    }

    public function testAppDirectoryReturnsCorrectPath(): void
    {
        $repository = new TestableRepository();
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->appsDirectory . DIRECTORY_SEPARATOR . 'my-app',
            $repository->callAppDirectory('my-app')
        );
    }

    public function testCommandDirectoryReturnsCorrectPath(): void
    {
        $repository = new TestableRepository();
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->appsDirectory . DIRECTORY_SEPARATOR . 'my-app' . DIRECTORY_SEPARATOR . 'Commands',
            $repository->callCommandDirectory('my-app')
        );
    }

    // --- 目录路径测试 ---

    public function testControllerDirectoryReturnsCorrectPath(): void
    {
        $repository = new TestableRepository();
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->appsDirectory . DIRECTORY_SEPARATOR . 'my-app' . DIRECTORY_SEPARATOR . 'Controllers',
            $repository->callControllerDirectory('my-app')
        );
    }

    public function testModelDirectoryReturnsCorrectPath(): void
    {
        $repository = new TestableRepository();
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->appsDirectory . DIRECTORY_SEPARATOR . 'my-app' . DIRECTORY_SEPARATOR . 'Models',
            $repository->callModelDirectory('my-app')
        );
    }

    public function testViewDirectoryReturnsCorrectPath(): void
    {
        $repository = new TestableRepository();
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->appsDirectory . DIRECTORY_SEPARATOR . 'my-app' . DIRECTORY_SEPARATOR . 'Views',
            $repository->callViewDirectory('my-app')
        );
    }

    public function testRenderReplacesSingleVariable(): void
    {
        $repository = new TestableRepository();
        $stub = 'Hello {{ name }}!';
        $vars = ['name' => 'World'];

        $this->assertSame('Hello World!', $repository->callRender($stub, $vars));
    }

    public function testRenderReplacesMultipleVariables(): void
    {
        $repository = new TestableRepository();
        $stub = '{{ greeting }} {{ name }}!';
        $vars = ['greeting' => 'Hello', 'name' => 'World'];

        $this->assertSame('Hello World!', $repository->callRender($stub, $vars));
    }

    // --- render 方法测试 ---

    public function testRenderReturnsOriginalWhenNoVariables(): void
    {
        $repository = new TestableRepository();
        $stub = 'Hello World!';
        $vars = [];

        $this->assertSame('Hello World!', $repository->callRender($stub, $vars));
    }

    public function testRenderHandlesDuplicateVariables(): void
    {
        $repository = new TestableRepository();
        $stub = '{{ name }} and {{ name }}';
        $vars = ['name' => 'World'];

        $this->assertSame('World and World', $repository->callRender($stub, $vars));
    }

    public function testRenderPreservesUnmatchedPlaceholders(): void
    {
        $repository = new TestableRepository();
        $stub = '{{ existing }} and {{ missing }}';
        $vars = ['existing' => 'value'];

        $this->assertSame('value and {{ missing }}', $repository->callRender($stub, $vars));
    }

    protected function setUp(): void
    {
        Platform::reset();
    }

    protected function tearDown(): void
    {
        Platform::reset();
    }
}
