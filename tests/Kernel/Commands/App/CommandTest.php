<?php declare(strict_types=1);

namespace Tests\Kernel\Commands\App;

use Composer\Autoload\ClassLoader;
use Evan755\Platform\Kernel\Commands\App\CreateCommand;
use Evan755\Platform\Kernel\Commands\App\DeleteCommand;
use Evan755\Platform\Kernel\Commands\App\IndexCommand;
use Evan755\Platform\Kernel\Platform;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CreateCommand::class)]
#[CoversClass(DeleteCommand::class)]
#[CoversClass(IndexCommand::class)]
class CommandTest extends TestCase
{
    protected string $appsDir;

    public function testCreateCommandExtendsBaseCommand(): void
    {
        $command = new CreateCommand();

        $this->assertInstanceOf(Command::class, $command);
    }

    public function testCreateCommandName(): void
    {
        $command = new CreateCommand();

        $this->assertSame('app:create', $command->getName());
    }

    // --- CreateCommand ---

    public function testCreateCommandAliases(): void
    {
        $command = new CreateCommand();

        $this->assertSame(['make:app'], $command->getAliases());
    }

    public function testCreateCommandDescription(): void
    {
        $command = new CreateCommand();

        $this->assertSame('Create a new application in the project', $command->getDescription());
    }

    public function testCreateCommandHasAppArgument(): void
    {
        $command = new CreateCommand();

        $this->assertTrue($command->getDefinition()->hasArgument('app'));
    }

    public function testCreateCommandAppArgumentIsRequired(): void
    {
        $command = new CreateCommand();
        $argument = $command->getDefinition()->getArgument('app');

        $this->assertTrue($argument->isRequired());
    }

    public function testCreateCommandAppArgumentDescription(): void
    {
        $command = new CreateCommand();
        $argument = $command->getDefinition()->getArgument('app');

        $this->assertSame('The name of the application', $argument->getDescription());
    }

    public function testCreateCommandExecuteReturnsSuccess(): void
    {
        $command = new CreateCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app']);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testDeleteCommandExtendsBaseCommand(): void
    {
        $command = new DeleteCommand();

        $this->assertInstanceOf(Command::class, $command);
    }

    public function testDeleteCommandName(): void
    {
        $command = new DeleteCommand();

        $this->assertSame('app:delete', $command->getName());
    }

    // --- DeleteCommand ---

    public function testDeleteCommandAliases(): void
    {
        $command = new DeleteCommand();

        $this->assertSame(['rm:app'], $command->getAliases());
    }

    public function testDeleteCommandDescription(): void
    {
        $command = new DeleteCommand();

        $this->assertSame('Delete an application from the platform', $command->getDescription());
    }

    public function testDeleteCommandHasAppArgument(): void
    {
        $command = new DeleteCommand();

        $this->assertTrue($command->getDefinition()->hasArgument('app'));
    }

    public function testDeleteCommandAppArgumentIsRequired(): void
    {
        $command = new DeleteCommand();
        $argument = $command->getDefinition()->getArgument('app');

        $this->assertTrue($argument->isRequired());
    }

    public function testDeleteCommandAppArgumentDescription(): void
    {
        $command = new DeleteCommand();
        $argument = $command->getDefinition()->getArgument('app');

        $this->assertSame('The name of the application', $argument->getDescription());
    }

    public function testDeleteCommandExecuteReturnsFailureWhenAppDoesNotExist(): void
    {
        $command = new DeleteCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app']);

        $this->assertSame(Command::FAILURE, $exitCode);
    }

    public function testIndexCommandExtendsBaseCommand(): void
    {
        $command = new IndexCommand();

        $this->assertInstanceOf(Command::class, $command);
    }

    public function testIndexCommandName(): void
    {
        $command = new IndexCommand();

        $this->assertSame('app:list', $command->getName());
    }

    // --- IndexCommand ---

    public function testIndexCommandAliases(): void
    {
        $command = new IndexCommand();

        $this->assertSame(['apps'], $command->getAliases());
    }

    public function testIndexCommandDescription(): void
    {
        $command = new IndexCommand();

        $this->assertSame('List applications in the project', $command->getDescription());
    }

    public function testIndexCommandHasNoArguments(): void
    {
        $command = new IndexCommand();

        $this->assertCount(0, $command->getDefinition()->getArguments());
    }

    public function testIndexCommandExecuteReturnsSuccess(): void
    {
        $command = new IndexCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    protected function setUp(): void
    {
        $rootDir = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        $this->appsDir = $rootDir . DIRECTORY_SEPARATOR . 'app';
    }

    protected function tearDown(): void
    {
        Platform::reset();

        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'my-app';
        if (is_dir($appDir)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appDir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($appDir);
        }
    }
}
