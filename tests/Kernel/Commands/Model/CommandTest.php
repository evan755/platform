<?php declare(strict_types=1);

namespace Tests\Kernel\Commands\Model;

use Composer\Autoload\ClassLoader;
use Evan755\Platform\Kernel\Commands\Model\CreateCommand;
use Evan755\Platform\Kernel\Commands\Model\DeleteCommand;
use Evan755\Platform\Kernel\Commands\Model\IndexCommand;
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

        $this->assertSame('model:create', $command->getName());
    }

    public function testCreateCommandAliases(): void
    {
        $command = new CreateCommand();

        $this->assertSame(['make:model'], $command->getAliases());
    }

    public function testCreateCommandDescription(): void
    {
        $command = new CreateCommand();

        $this->assertSame('Create a new model in an application', $command->getDescription());
    }

    // --- CreateCommand ---

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

    public function testCreateCommandHasNameArgument(): void
    {
        $command = new CreateCommand();

        $this->assertTrue($command->getDefinition()->hasArgument('name'));
    }

    public function testCreateCommandNameArgumentIsRequired(): void
    {
        $command = new CreateCommand();
        $argument = $command->getDefinition()->getArgument('name');

        $this->assertTrue($argument->isRequired());
    }

    public function testCreateCommandNameArgumentDescription(): void
    {
        $command = new CreateCommand();
        $argument = $command->getDefinition()->getArgument('name');

        $this->assertSame('The name of the model to create', $argument->getDescription());
    }

    public function testCreateCommandExecuteReturnsFailureWhenAppDoesNotExist(): void
    {
        $command = new CreateCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'nonexistent', 'name' => 'Session']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testCreateCommandExecuteReturnsFailureWhenModelAlreadyExists(): void
    {
        $this->createApp('my-app');
        $this->createModelFile('my-app', 'Session');

        $command = new CreateCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app', 'name' => 'Session']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('already exists', $tester->getDisplay());
    }

    protected function createApp(string $name): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . $name;
        $dirs = [$appDir, $appDir . DIRECTORY_SEPARATOR . 'Models'];
        foreach ($dirs as $dir) {
            is_dir($dir) or mkdir($dir, 0755, true);
        }
        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', json_encode([
            'name' => $name,
            'database' => ['uri' => '', 'name' => $name . '_db'],
        ]));
        Platform::reset();
    }

    protected function createModelFile(string $app, string $model): void
    {
        $path = $this->appsDir . DIRECTORY_SEPARATOR . $app . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $model . '.php';
        file_put_contents($path, '<?php');
    }

    public function testCreateCommandExecuteReturnsSuccess(): void
    {
        $this->createApp('my-app');

        $command = new CreateCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app', 'name' => 'Session']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('created', $tester->getDisplay());
    }

    public function testDeleteCommandExtendsBaseCommand(): void
    {
        $command = new DeleteCommand();

        $this->assertInstanceOf(Command::class, $command);
    }

    public function testDeleteCommandName(): void
    {
        $command = new DeleteCommand();

        $this->assertSame('model:delete', $command->getName());
    }

    // --- DeleteCommand ---

    public function testDeleteCommandAliases(): void
    {
        $command = new DeleteCommand();

        $this->assertSame(['rm:model'], $command->getAliases());
    }

    public function testDeleteCommandDescription(): void
    {
        $command = new DeleteCommand();

        $this->assertSame('Delete a model from an application', $command->getDescription());
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

    public function testDeleteCommandHasNameArgument(): void
    {
        $command = new DeleteCommand();

        $this->assertTrue($command->getDefinition()->hasArgument('name'));
    }

    public function testDeleteCommandNameArgumentIsRequired(): void
    {
        $command = new DeleteCommand();
        $argument = $command->getDefinition()->getArgument('name');

        $this->assertTrue($argument->isRequired());
    }

    public function testDeleteCommandNameArgumentDescription(): void
    {
        $command = new DeleteCommand();
        $argument = $command->getDefinition()->getArgument('name');

        $this->assertSame('The name of the model to delete', $argument->getDescription());
    }

    public function testDeleteCommandExecuteReturnsFailureWhenAppDoesNotExist(): void
    {
        $command = new DeleteCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'nonexistent', 'name' => 'Session']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testDeleteCommandExecuteReturnsFailureWhenModelDoesNotExist(): void
    {
        $this->createApp('my-app');

        $command = new DeleteCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app', 'name' => 'Session']);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    public function testDeleteCommandExecuteReturnsSuccess(): void
    {
        $this->createApp('my-app');
        $this->createModelFile('my-app', 'Session');

        $command = new DeleteCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app', 'name' => 'Session']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('deleted', $tester->getDisplay());
    }

    public function testIndexCommandExtendsBaseCommand(): void
    {
        $command = new IndexCommand();

        $this->assertInstanceOf(Command::class, $command);
    }

    public function testIndexCommandName(): void
    {
        $command = new IndexCommand();

        $this->assertSame('model:list', $command->getName());
    }

    // --- IndexCommand ---

    public function testIndexCommandAliases(): void
    {
        $command = new IndexCommand();

        $this->assertSame(['models'], $command->getAliases());
    }

    public function testIndexCommandDescription(): void
    {
        $command = new IndexCommand();

        $this->assertSame('List models in an application', $command->getDescription());
    }

    public function testIndexCommandHasAppArgument(): void
    {
        $command = new IndexCommand();

        $this->assertTrue($command->getDefinition()->hasArgument('app'));
    }

    public function testIndexCommandAppArgumentIsOptional(): void
    {
        $command = new IndexCommand();
        $argument = $command->getDefinition()->getArgument('app');

        $this->assertFalse($argument->isRequired());
    }

    public function testIndexCommandAppArgumentDescription(): void
    {
        $command = new IndexCommand();
        $argument = $command->getDefinition()->getArgument('app');

        $this->assertSame('The name of the application (lists all if omitted)', $argument->getDescription());
    }

    public function testIndexCommandExecuteReturnsSuccessListingAllApps(): void
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

    // TODO: Re-enable when IndexCommand logic is implemented
    // public function testIndexCommandExecuteReturnsFailureWhenAppDoesNotExist(): void {}
    // public function testIndexCommandExecuteReturnsSuccessWithModels(): void {}
    // public function testIndexCommandExecuteReturnsSuccessWithNoModels(): void {}

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
