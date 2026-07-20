<?php declare(strict_types=1);

namespace Tests\Kernel\Repositories;

use Composer\Autoload\ClassLoader;
use Evan755\Platform\Kernel\Platform;
use Evan755\Platform\Kernel\Repositories\CommandRepository;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

#[CoversClass(CommandRepository::class)]
class CommandRepositoryTest extends TestCase
{
    protected string $appsDir;
    protected CommandRepository $repository;

    public function testCommandRepositoryExtendsRepository(): void
    {
        $this->assertInstanceOf('Evan755\Platform\Kernel\Repository', $this->repository);
    }

    public function testIndexMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'index'));
    }

    public function testIndexMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
        $method = $reflection->getMethod('index');

        $this->assertTrue($method->isPublic());
    }

    // --- 结构测试 ---

    public function testIndexMethodAcceptsOneParameter(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
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

    public function testIndexReturnsEmptyArrayWhenNoCommands(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();

        $this->assertSame([], $repository->index('test-app'));
    }

    protected function createAppWithCommands(string $app, array $commands = []): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . $app;
        $commandsDir = $appDir . DIRECTORY_SEPARATOR . 'Commands';
        mkdir($commandsDir, 0755, true);

        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', json_encode([
            'name' => $app,
            'database' => ['uri' => '', 'name' => $app . '_db'],
        ]));

        foreach ($commands as $command) {
            file_put_contents($commandsDir . DIRECTORY_SEPARATOR . $command . 'Command.php', '<?php');
        }

        Platform::reset();
    }

    public function testIndexReturnsCommands(): void
    {
        $this->createAppWithCommands('test-app', ['Welcome', 'Deploy']);
        $repository = new CommandRepository();
        $result = $repository->index('test-app');

        $this->assertCount(2, $result);
        $this->assertContains('Welcome', $result);
        $this->assertContains('Deploy', $result);
    }

    public function testIndexSkipsNonPhpFiles(): void
    {
        $this->createAppWithCommands('test-app', ['Welcome']);
        $commandsDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands';
        file_put_contents($commandsDir . DIRECTORY_SEPARATOR . 'README.md', '# Commands');

        $repository = new CommandRepository();
        $result = $repository->index('test-app');

        $this->assertCount(1, $result);
        $this->assertContains('Welcome', $result);
    }

    public function testIndexStripsCommandSuffix(): void
    {
        $this->createAppWithCommands('test-app', ['Welcome']);
        $repository = new CommandRepository();
        $result = $repository->index('test-app');

        $this->assertContains('Welcome', $result);
        $this->assertNotContains('WelcomeCommand', $result);
    }

    public function testCreateMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'create'));
    }

    public function testCreateMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertTrue($method->isPublic());
    }

    // --- create 方法测试 ---

    public function testCreateMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
        $method = $reflection->getMethod('create');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('command', $method->getParameters()[1]->getName());
    }

    public function testCreateReturnsTrueOnSuccess(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();

        $this->assertTrue($repository->create('test-app', 'Deploy'));
    }

    public function testCreateCreatesCommandFile(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();
        $repository->create('test-app', 'Deploy');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'DeployCommand.php';
        $this->assertFileExists($commandFile);
    }

    public function testCreateCommandFileContainsValidPhp(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();
        $repository->create('test-app', 'Deploy');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'DeployCommand.php';
        $content = file_get_contents($commandFile);

        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('class DeployCommand extends Command', $content);
    }

    public function testCreateCommandFileContainsCorrectNamespace(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();
        $repository->create('test-app', 'Deploy');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'DeployCommand.php';
        $content = file_get_contents($commandFile);

        $this->assertStringContainsString('namespace App\\test-app\\Commands;', $content);
    }

    public function testCreateCommandFileContainsCommandName(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();
        $repository->create('test-app', 'Deploy');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'DeployCommand.php';
        $content = file_get_contents($commandFile);

        $this->assertStringContainsString("setName('test-app:deploy')", $content);
    }

    public function testCreateCreatesNestedCommandFile(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();
        $repository->create('test-app', 'Database/Migrate');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'MigrateCommand.php';
        $this->assertFileExists($commandFile);
    }

    public function testCreateNestedCommandFileContainsCorrectNamespace(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();
        $repository->create('test-app', 'Database/Migrate');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'MigrateCommand.php';
        $content = file_get_contents($commandFile);

        $this->assertStringContainsString('namespace App\\test-app\\Commands\\Database;', $content);
    }

    public function testCreateNestedCommandFileContainsCorrectCommandName(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();
        $repository->create('test-app', 'Database/Migrate');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'MigrateCommand.php';
        $content = file_get_contents($commandFile);

        $this->assertStringContainsString("setName('test-app:database:migrate')", $content);
    }

    public function testDeleteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'delete'));
    }

    public function testDeleteMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertTrue($method->isPublic());
    }

    // --- delete 方法测试 ---

    public function testDeleteMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
        $method = $reflection->getMethod('delete');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('command', $method->getParameters()[1]->getName());
    }

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->createAppWithCommands('test-app', ['Welcome']);
        $repository = new CommandRepository();

        $this->assertTrue($repository->delete('test-app', 'Welcome'));
    }

    public function testDeleteRemovesCommandFile(): void
    {
        $this->createAppWithCommands('test-app', ['Welcome']);
        $repository = new CommandRepository();
        $repository->delete('test-app', 'Welcome');

        $commandFile = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app' . DIRECTORY_SEPARATOR . 'Commands' . DIRECTORY_SEPARATOR . 'WelcomeCommand.php';
        $this->assertFileDoesNotExist($commandFile);
    }

    public function testDeleteReturnsFalseWhenCommandDoesNotExist(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();

        $this->assertFalse($repository->delete('test-app', 'Nonexistent'));
    }

    public function testExistsMethodExists(): void
    {
        $this->assertTrue(method_exists($this->repository, 'exists'));
    }

    public function testExistsMethodIsPublic(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertTrue($method->isPublic());
    }

    // --- exists 方法测试 ---

    public function testExistsMethodAcceptsTwoParameters(): void
    {
        $reflection = new ReflectionClass(CommandRepository::class);
        $method = $reflection->getMethod('exists');

        $this->assertCount(2, $method->getParameters());
        $this->assertSame('app', $method->getParameters()[0]->getName());
        $this->assertSame('command', $method->getParameters()[1]->getName());
    }

    public function testExistsReturnsTrueWhenCommandExists(): void
    {
        $this->createAppWithCommands('test-app', ['Welcome']);
        $repository = new CommandRepository();

        $this->assertTrue($repository->exists('test-app', 'Welcome'));
    }

    public function testExistsReturnsFalseWhenCommandDoesNotExist(): void
    {
        $this->createAppWithCommands('test-app');
        $repository = new CommandRepository();

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
        $this->repository = new CommandRepository();
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
