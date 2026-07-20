<?php declare(strict_types=1);

namespace Tests\Kernel;

use Evan755\Platform\Kernel\Model;
use Evan755\Platform\Kernel\Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class TestModel extends Model
{
    protected string $collections = 'tests';

    public function __construct(string $app = 'test-app')
    {
        $this->app = $app;
    }
}

class Session extends Model
{
    public function __construct(string $app = 'test-app')
    {
        $this->app = $app;
    }
}

class Person extends Model
{
    public function __construct(string $app = 'test-app')
    {
        $this->app = $app;
    }
}

class Category extends Model
{
    public function __construct(string $app = 'test-app')
    {
        $this->app = $app;
    }
}

#[CoversClass(Model::class)]
class ModelTest extends TestCase
{
    public function testModelIsAbstract(): void
    {
        $reflection = new ReflectionClass(Model::class);

        $this->assertTrue($reflection->isAbstract());
    }

    public function testModelHasCollectionsProperty(): void
    {
        $reflection = new ReflectionProperty(Model::class, 'collections');

        $this->assertTrue($reflection->isProtected());
        $this->assertSame('string', $reflection->getType()->getName());
    }

    // --- Structure Tests ---

    public function testModelHasDatabaseProperty(): void
    {
        $reflection = new ReflectionProperty(Model::class, 'database');

        $this->assertTrue($reflection->isProtected());
        $this->assertSame('MongoDB\Database', $reflection->getType()->getName());
    }

    public function testModelHasCollectionProperty(): void
    {
        $reflection = new ReflectionProperty(Model::class, 'collection');

        $this->assertTrue($reflection->isProtected());
        $this->assertSame('MongoDB\Collection', $reflection->getType()->getName());
    }

    public function testConstructorAcceptsAppParameter(): void
    {
        $reflection = new ReflectionClass(Model::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        $this->assertSame('app', $constructor->getParameters()[0]->getName());
        $this->assertSame('string', $constructor->getParameters()[0]->getType()->getName());
    }

    public function testFindMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'find');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(2, $reflection->getParameters());
    }

    // --- Constructor Tests ---

    public function testFindOneMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'findOne');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(2, $reflection->getParameters());
    }

    // --- Method Signature Tests ---

    public function testInsertMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'insert');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(1, $reflection->getParameters());
    }

    public function testUpdateMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'update');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(3, $reflection->getParameters());
    }

    public function testDeleteMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'delete');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(1, $reflection->getParameters());
    }

    public function testCountMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'count');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(1, $reflection->getParameters());
    }

    public function testResolveCollectionUsesExplicitCollections(): void
    {
        $model = new TestModel();
        $reflection = new ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('resolveCollection');
        $method->setAccessible(true);

        $this->assertSame('tests', $method->invoke($model));
    }

    public function testResolveCollectionAutoGeneratesFromClassName(): void
    {
        $model = new Session();
        $reflection = new ReflectionClass(Session::class);
        $method = $reflection->getMethod('resolveCollection');
        $method->setAccessible(true);

        $this->assertSame('sessions', $method->invoke($model));
    }

    // --- ResolveCollection Tests ---

    public function testResolveCollectionPluralizesPerson(): void
    {
        $model = new Person();
        $reflection = new ReflectionClass(Person::class);
        $method = $reflection->getMethod('resolveCollection');
        $method->setAccessible(true);

        $this->assertSame('people', $method->invoke($model));
    }

    public function testResolveCollectionPluralizesCategory(): void
    {
        $model = new Category();
        $reflection = new ReflectionClass(Category::class);
        $method = $reflection->getMethod('resolveCollection');
        $method->setAccessible(true);

        $this->assertSame('categories', $method->invoke($model));
    }

    public function testConfigReturnsDefaultWhenAppNotFound(): void
    {
        $model = new TestModel('nonexistent-app');
        $reflection = new ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('config');
        $method->setAccessible(true);

        $result = $method->invoke($model);

        $this->assertSame(['uri' => '', 'name' => 'nonexistent-app_db'], $result);
    }

    public function testConfigReturnsAppDatabaseConfig(): void
    {
        $appsDir = Platform::getInstance()->appsDirectory;
        $appDir = $appsDir . DIRECTORY_SEPARATOR . 'test-app';
        $appJson = $appDir . DIRECTORY_SEPARATOR . 'App.json';

        mkdir($appDir, 0755, true);
        file_put_contents($appJson, json_encode([
            'name' => 'test-app',
            'database' => [
                'uri' => 'mongodb://localhost:27017',
                'name' => 'test_db',
            ],
        ]));

        Platform::reset();

        $model = new TestModel('test-app');
        $reflection = new ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('config');
        $method->setAccessible(true);

        $result = $method->invoke($model);

        $this->assertSame('mongodb://localhost:27017', $result['uri']);
        $this->assertSame('test_db', $result['name']);

        // Cleanup
        unlink($appJson);
        rmdir($appDir);
        Platform::reset();
    }

    // --- Config Tests ---

    protected function setUp(): void
    {
        Platform::reset();
    }

    protected function tearDown(): void
    {
        Platform::reset();
    }
}
