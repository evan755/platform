<?php declare(strict_types=1);

namespace Tests\Kernel;

use Evan755\Platform\Kernel\Model;
use Evan755\Platform\Kernel\Platform;
use MongoDB\BSON\UTCDateTime;
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

    public function testSoftDeleteMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'softDelete');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(1, $reflection->getParameters());
    }

    public function testCountMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'count');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(1, $reflection->getParameters());
    }

    public function testWithTrashedMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'withTrashed');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(0, $reflection->getParameters());
    }

    public function testOnlyTrashedMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'onlyTrashed');

        $this->assertTrue($reflection->isPublic());
        $this->assertCount(2, $reflection->getParameters());
    }

    public function testNowMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'now');

        $this->assertTrue($reflection->isProtected());
        $this->assertCount(0, $reflection->getParameters());
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

    // --- now() 方法测试 ---

    public function testNowReturnsUTCDateTimeInstance(): void
    {
        $model = new TestModel();
        $reflection = new ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('now');
        $method->setAccessible(true);

        $result = $method->invoke($model);

        $this->assertInstanceOf(UTCDateTime::class, $result);
    }

    public function testNowReturnsCurrentTime(): void
    {
        $model = new TestModel();
        $reflection = new ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('now');
        $method->setAccessible(true);

        $before = new UTCDateTime();
        $result = $method->invoke($model);
        $after = new UTCDateTime();

        $this->assertGreaterThanOrEqual($before->toDateTime()->getTimestamp(), $result->toDateTime()->getTimestamp());
        $this->assertLessThanOrEqual($after->toDateTime()->getTimestamp(), $result->toDateTime()->getTimestamp());
    }

    // --- 方法返回类型测试 ---

    public function testFindMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'find');

        $this->assertSame('MongoDB\Driver\CursorInterface', $reflection->getReturnType()->getName());
    }

    public function testFindOneMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'findOne');

        $this->assertTrue($reflection->getReturnType() instanceof \ReflectionUnionType);
    }

    public function testInsertMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'insert');

        $this->assertSame('mixed', $reflection->getReturnType()->getName());
    }

    public function testUpdateMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'update');

        $this->assertSame('int', $reflection->getReturnType()->getName());
    }

    public function testDeleteMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'delete');

        $this->assertSame('int', $reflection->getReturnType()->getName());
    }

    public function testSoftDeleteMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'softDelete');

        $this->assertSame('int', $reflection->getReturnType()->getName());
    }

    public function testCountMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'count');

        $this->assertSame('int', $reflection->getReturnType()->getName());
    }

    public function testWithTrashedMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'withTrashed');

        $this->assertSame('array', $reflection->getReturnType()->getName());
    }

    public function testOnlyTrashedMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'onlyTrashed');

        $this->assertSame('MongoDB\Driver\CursorInterface', $reflection->getReturnType()->getName());
    }

    // --- 方法参数类型测试 ---

    public function testFindMethodParameterTypes(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'find');
        $params = $reflection->getParameters();

        $this->assertSame('filter', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());
        $this->assertTrue($params[0]->isOptional());

        $this->assertSame('options', $params[1]->getName());
        $this->assertSame('array', $params[1]->getType()->getName());
        $this->assertTrue($params[1]->isOptional());
    }

    public function testInsertMethodParameterTypes(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'insert');
        $params = $reflection->getParameters();

        $this->assertSame('document', $params[0]->getName());
        $this->assertTrue($params[0]->getType() instanceof \ReflectionUnionType);
    }

    public function testUpdateMethodParameterTypes(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'update');
        $params = $reflection->getParameters();

        $this->assertSame('filter', $params[0]->getName());
        $this->assertSame('array', $params[0]->getType()->getName());

        $this->assertSame('update', $params[1]->getName());
        $this->assertTrue($params[1]->getType() instanceof \ReflectionUnionType);

        $this->assertSame('options', $params[2]->getName());
        $this->assertTrue($params[2]->isOptional());
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
