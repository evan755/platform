<?php declare(strict_types=1);

namespace Tests\Kernel\Commands\Model;

use Evan755\Platform\Kernel\Commands\Model\CreateCommand;
use Evan755\Platform\Kernel\Commands\Model\DeleteCommand;
use Evan755\Platform\Kernel\Commands\Model\IndexCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(CreateCommand::class)]
#[CoversClass(DeleteCommand::class)]
#[CoversClass(IndexCommand::class)]
class CommandTest extends TestCase
{
    // --- CreateCommand ---

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

    public function testCreateCommandExecuteReturnsSuccess(): void
    {
        $command = new CreateCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app', 'name' => 'my-model']);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    // --- DeleteCommand ---

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

    public function testDeleteCommandExecuteReturnsSuccess(): void
    {
        $command = new DeleteCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app', 'name' => 'my-model']);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    // --- IndexCommand ---

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

    public function testIndexCommandExecuteReturnsSuccess(): void
    {
        $command = new IndexCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testIndexCommandExecuteWithAppReturnsSuccess(): void
    {
        $command = new IndexCommand();
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['app' => 'my-app']);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
