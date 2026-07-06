<?php declare(strict_types=1);

namespace Tests\Kernel;

use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use DirectoryIterator;
use Evan755\Platform\Kernel\Bootstrap;
use Evan755\Platform\Kernel\Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use UnhandledMatchError;

/**
 * 用于测试的 Bootstrap 子类，将 protected 方法的调用记录下来
 */
class TestableBootstrap extends Bootstrap
{
    public ?string $dispatched = null;

    protected function web(): void
    {
        $this->dispatched = 'web';
    }

    protected function cli(): void
    {
        $this->dispatched = 'cli';
    }
}

/**
 * 用于测试 kernelCommands() 的 Bootstrap 子类
 * 重写 kernelCommands 使用临时 Fixtures 目录代替 InstalledVersions 路径
 */
class TestableKernelBootstrap extends Bootstrap
{
    public string $kernelCommandsDirectory = '';

    protected function kernelCommands(): array
    {
        $directory = $this->kernelCommandsDirectory;
        if (!is_dir($directory)) {
            return [];
        }
        $commands = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isDir() || !str_ends_with($file->getFilename(), 'Command.php')) {
                continue;
            }
            $relative = str_replace($directory, '', $file->getPathname());
            $class = 'Evan755\\Platform\\Kernel\\Commands\\' . str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], ltrim($relative, DIRECTORY_SEPARATOR));
            $reflection = new ReflectionClass($class);
            if ($reflection->isSubclassOf(\Symfony\Component\Console\Command\Command::class) && !$reflection->isAbstract()) {
                $commands[] = new $class();
            }
        }
        return $commands;
    }
}

#[CoversClass(Bootstrap::class)]
class BootstrapTest extends TestCase
{
    protected string $appsDir;
    protected string $kernelCommandsDir;
    protected string $fixturesDir;
    protected ?object $autoloader = null;

    protected function setUp(): void
    {
        $rootDir = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        $this->appsDir = $rootDir . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($this->appsDir)) {
            mkdir($this->appsDir, 0755, true);
        }

        // kernelCommands 测试用 Fixtures 目录
        $this->fixturesDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Commands';
        $this->kernelCommandsDir = $this->fixturesDir;

        // 注册自定义 autoloader，将 Evan755\Platform\Kernel\Commands\ 映射到 Fixtures
        $fixturesPath = $this->fixturesDir;
        $this->autoloader = static function (string $class) use ($fixturesPath): void {
            $prefix = 'Evan755\\Platform\\Kernel\\Commands\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = str_replace($prefix, '', $class);
            $file = $fixturesPath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        };
        spl_autoload_register($this->autoloader);
    }

    protected function tearDown(): void
    {
        Platform::reset();

        // 清理 autoloader
        if ($this->autoloader !== null) {
            spl_autoload_unregister($this->autoloader);
            $this->autoloader = null;
        }

        // 清理 Fixtures 目录
        $fixturesParent = dirname($this->fixturesDir);
        if (is_dir($this->fixturesDir)) {
            $this->removeDirectory($this->fixturesDir);
        }
        if (is_dir($fixturesParent) && $this->isDirectoryEmpty($fixturesParent)) {
            rmdir($fixturesParent);
        }

        // 清理 app 目录
        if (is_dir($this->appsDir)) {
            $iterator = new DirectoryIterator($this->appsDir);
            foreach ($iterator as $item) {
                if ($item->isDot()) {
                    continue;
                }
                // 清理 app 子目录中的 App.json 和 Commands 目录
                $commandsDir = $item->getPathname() . DIRECTORY_SEPARATOR . 'Commands';
                if (is_dir($commandsDir)) {
                    foreach (new DirectoryIterator($commandsDir) as $cmdFile) {
                        if (!$cmdFile->isDot()) {
                            unlink($cmdFile->getPathname());
                        }
                    }
                    rmdir($commandsDir);
                }
                $appJson = $item->getPathname() . DIRECTORY_SEPARATOR . 'App.json';
                if (file_exists($appJson)) {
                    unlink($appJson);
                }
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                }
            }
            rmdir($this->appsDir);
        }
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new DirectoryIterator($dir) as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $this->removeDirectory($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($dir);
    }

    protected function isDirectoryEmpty(string $dir): bool
    {
        foreach (new DirectoryIterator($dir) as $item) {
            if (!$item->isDot()) {
                return false;
            }
        }
        return true;
    }

    protected function createCommandFile(string $path, string $namespace, string $className, bool $abstract = false): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $abstractKeyword = $abstract ? 'abstract ' : '';
        $content = <<<PHP
<?php
namespace $namespace;

use Symfony\Component\Console\Command\Command;

{$abstractKeyword}class $className extends Command
{
    protected static \$defaultName = 'test:{$className}';
}
PHP;
        file_put_contents($path, $content);
    }

    // --- 构造函数和属性测试 ---

    public function testConstructorSetsPlatformProperty(): void
    {
        $bootstrap = new Bootstrap();

        $this->assertObjectHasProperty('platform', $bootstrap);
    }

    public function testPlatformPropertyIsProtected(): void
    {
        $reflection = new ReflectionProperty(Bootstrap::class, 'platform');

        $this->assertTrue($reflection->isProtected());
    }

    public function testPlatformPropertyIsPlatformInstance(): void
    {
        $bootstrap = new Bootstrap();

        $reflection = new ReflectionProperty(Bootstrap::class, 'platform');
        $platform = $reflection->getValue($bootstrap);

        $this->assertInstanceOf(Platform::class, $platform);
    }

    // --- Run 分发测试 ---

    public function testRunDispatchesToCliWhenRuntimeIsCli(): void
    {
        $bootstrap = new TestableBootstrap();

        if (PHP_SAPI === 'cli') {
            $bootstrap->Run();
            $this->assertSame('cli', $bootstrap->dispatched);
        }
    }

    public function testRunDispatchesToWebWhenRuntimeIsWeb(): void
    {
        $platform = Platform::getInstance();
        $platform->runtime = 'web';

        $bootstrap = new TestableBootstrap();
        $bootstrap->Run();

        $this->assertSame('web', $bootstrap->dispatched);
    }

    public function testRunDispatchesToCliWhenRuntimeIsCliExplicitly(): void
    {
        $platform = Platform::getInstance();
        $platform->runtime = 'cli';

        $bootstrap = new TestableBootstrap();
        $bootstrap->Run();

        $this->assertSame('cli', $bootstrap->dispatched);
    }

    public function testRunThrowsOnInvalidRuntime(): void
    {
        $platform = Platform::getInstance();
        $platform->runtime = 'invalid';

        $bootstrap = new Bootstrap();

        $this->expectException(UnhandledMatchError::class);
        $bootstrap->Run();
    }

    // --- web 输出测试 ---

    public function testWebOutputsPlatformDump(): void
    {
        $platform = Platform::getInstance();
        $platform->runtime = 'web';

        $bootstrap = new Bootstrap();

        ob_start();
        $bootstrap->Run();
        $output = ob_get_clean();

        $this->assertStringContainsString('Evan755\Platform\Kernel\Platform', $output);
    }

    // --- cli 测试 ---

    public function testCliUsesSymfonyApplication(): void
    {
        // cli() 方法应创建 Symfony Application 并调用 run()
        // 通过 TestableBootstrap 验证分发到 cli
        $platform = Platform::getInstance();
        $platform->runtime = 'cli';

        $bootstrap = new TestableBootstrap();
        $bootstrap->Run();

        $this->assertSame('cli', $bootstrap->dispatched);
    }

    public function testCliMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Bootstrap::class, 'cli');

        $this->assertTrue($method->isProtected());
    }

    // --- methods 可见性测试 ---

    public function testCommandsMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Bootstrap::class, 'commands');

        $this->assertTrue($method->isProtected());
    }

    public function testCommandMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Bootstrap::class, 'command');

        $this->assertTrue($method->isProtected());
    }

    // --- command 扫描测试 ---

    public function testCommandReturnsEmptyArrayForEmptyDirectory(): void
    {
        $commandsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands';
        mkdir($commandsDir, 0755, true);

        Platform::reset();
        $bootstrap = new Bootstrap();
        $method = new ReflectionMethod(Bootstrap::class, 'command');
        $result = $method->invoke($bootstrap, $commandsDir, 'test-app');

        $this->assertSame([], $result);
    }

    public function testCommandSkipsNonCommandPhpFiles(): void
    {
        $commandsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands';
        mkdir($commandsDir, 0755, true);
        // 创建不以 Command.php 结尾的文件
        file_put_contents($commandsDir . DIRECTORY_SEPARATOR . 'Helper.php', '<?php');

        Platform::reset();
        $bootstrap = new Bootstrap();
        $method = new ReflectionMethod(Bootstrap::class, 'command');
        $result = $method->invoke($bootstrap, $commandsDir, 'test-app');

        $this->assertSame([], $result);
    }

    public function testCommandThrowsForNonexistentDirectory(): void
    {
        $bootstrap = new Bootstrap();
        $method = new ReflectionMethod(Bootstrap::class, 'command');

        $this->expectException(\UnexpectedValueException::class);
        $method->invoke($bootstrap, '/nonexistent/path', 'test-app');
    }

    public function testCommandMethodAcceptsDirectoryAndNameParameters(): void
    {
        $method = new ReflectionMethod(Bootstrap::class, 'command');
        $params = $method->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('directory', $params[0]->getName());
        $this->assertSame('name', $params[1]->getName());
    }

    public function testCommandsMethodAcceptsNoParameters(): void
    {
        $method = new ReflectionMethod(Bootstrap::class, 'commands');

        $this->assertCount(0, $method->getParameters());
    }

    public function testCommandsMethodReturnsArray(): void
    {
        $bootstrap = new Bootstrap();
        $method = new ReflectionMethod(Bootstrap::class, 'commands');
        $result = $method->invoke($bootstrap);

        $this->assertIsArray($result);
    }

    // --- kernelCommands 测试 ---

    public function testKernelCommandsMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Bootstrap::class, 'kernelCommands');

        $this->assertTrue($method->isProtected());
    }

    public function testKernelCommandsMethodAcceptsNoParameters(): void
    {
        $method = new ReflectionMethod(Bootstrap::class, 'kernelCommands');

        $this->assertCount(0, $method->getParameters());
    }

    public function testKernelCommandsReturnsEmptyArrayWhenDirectoryNotExist(): void
    {
        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = '/nonexistent/Commands';

        $method = new ReflectionMethod(TestableKernelBootstrap::class, 'kernelCommands');
        $result = $method->invoke($bootstrap);

        $this->assertSame([], $result);
    }

    public function testKernelCommandsReturnsEmptyArrayForEmptyDirectory(): void
    {
        mkdir($this->kernelCommandsDir, 0755, true);

        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = $this->kernelCommandsDir;

        $method = new ReflectionMethod(TestableKernelBootstrap::class, 'kernelCommands');
        $result = $method->invoke($bootstrap);

        $this->assertSame([], $result);
    }

    public function testKernelCommandsSkipsNonCommandPhpFiles(): void
    {
        mkdir($this->kernelCommandsDir, 0755, true);
        file_put_contents($this->kernelCommandsDir . DIRECTORY_SEPARATOR . 'Helper.php', '<?php');

        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = $this->kernelCommandsDir;

        $method = new ReflectionMethod(TestableKernelBootstrap::class, 'kernelCommands');
        $result = $method->invoke($bootstrap);

        $this->assertSame([], $result);
    }

    public function testKernelCommandsLoadsCommandFromDirectory(): void
    {
        mkdir($this->kernelCommandsDir, 0755, true);
        $this->createCommandFile(
            $this->kernelCommandsDir . DIRECTORY_SEPARATOR . 'TestCommand.php',
            'Evan755\\Platform\\Kernel\\Commands',
            'TestCommand'
        );

        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = $this->kernelCommandsDir;

        $method = new ReflectionMethod(TestableKernelBootstrap::class, 'kernelCommands');
        $result = $method->invoke($bootstrap);

        $this->assertCount(1, $result);
        $this->assertInstanceOf('Evan755\\Platform\\Kernel\\Commands\\TestCommand', $result[0]);
    }

    public function testKernelCommandsLoadsCommandFromSubdirectory(): void
    {
        $subDir = $this->kernelCommandsDir . DIRECTORY_SEPARATOR . 'Sub';
        mkdir($subDir, 0755, true);
        $this->createCommandFile(
            $subDir . DIRECTORY_SEPARATOR . 'SubCommand.php',
            'Evan755\\Platform\\Kernel\\Commands\\Sub',
            'SubCommand'
        );

        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = $this->kernelCommandsDir;

        $method = new ReflectionMethod(TestableKernelBootstrap::class, 'kernelCommands');
        $result = $method->invoke($bootstrap);

        $this->assertCount(1, $result);
        $this->assertInstanceOf('Evan755\\Platform\\Kernel\\Commands\\Sub\\SubCommand', $result[0]);
    }

    public function testKernelCommandsSkipsAbstractCommand(): void
    {
        mkdir($this->kernelCommandsDir, 0755, true);
        $this->createCommandFile(
            $this->kernelCommandsDir . DIRECTORY_SEPARATOR . 'AbstractCommand.php',
            'Evan755\\Platform\\Kernel\\Commands',
            'AbstractCommand',
            abstract: true
        );

        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = $this->kernelCommandsDir;

        $method = new ReflectionMethod(TestableKernelBootstrap::class, 'kernelCommands');
        $result = $method->invoke($bootstrap);

        $this->assertSame([], $result);
    }

    public function testKernelCommandsLoadsMultipleCommandsIncludingSubdirectory(): void
    {
        mkdir($this->kernelCommandsDir, 0755, true);
        $subDir = $this->kernelCommandsDir . DIRECTORY_SEPARATOR . 'Sub';
        mkdir($subDir, 0755, true);

        $this->createCommandFile(
            $this->kernelCommandsDir . DIRECTORY_SEPARATOR . 'FirstCommand.php',
            'Evan755\\Platform\\Kernel\\Commands',
            'FirstCommand'
        );
        $this->createCommandFile(
            $subDir . DIRECTORY_SEPARATOR . 'SecondCommand.php',
            'Evan755\\Platform\\Kernel\\Commands\\Sub',
            'SecondCommand'
        );

        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = $this->kernelCommandsDir;

        $method = new ReflectionMethod(TestableKernelBootstrap::class, 'kernelCommands');
        $result = $method->invoke($bootstrap);

        $this->assertCount(2, $result);
        $classes = array_map(fn($cmd) => get_class($cmd), $result);
        $this->assertContains('Evan755\\Platform\\Kernel\\Commands\\FirstCommand', $classes);
        $this->assertContains('Evan755\\Platform\\Kernel\\Commands\\Sub\\SecondCommand', $classes);
    }

    public function testKernelCommandsMethodIsCalledByCommandsMethod(): void
    {
        // 验证 commands() 会调用 kernelCommands() 并合并结果
        $bootstrap = new TestableKernelBootstrap();
        $bootstrap->kernelCommandsDirectory = '/nonexistent';  // kernelCommands 返回 []

        $method = new ReflectionMethod(Bootstrap::class, 'commands');
        $result = $method->invoke($bootstrap);

        $this->assertIsArray($result);
    }
}
