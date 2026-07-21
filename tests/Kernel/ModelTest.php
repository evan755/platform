<?php declare(strict_types=1);

namespace Tests\Kernel;

use Evan755\Platform\Kernel\Model;
use Evan755\Platform\Kernel\Platform;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
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

    // --- 结构测试 ---

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

    public function testConfigMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'config');

        $this->assertTrue($reflection->isProtected());
        $this->assertCount(0, $reflection->getParameters());
    }

    // --- 构造函数测试 ---

    public function testCollectionMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'collection');

        $this->assertTrue($reflection->isProtected());
        $this->assertCount(0, $reflection->getParameters());
    }

    // --- 方法存在性测试 ---

    public function testNowMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'now');

        $this->assertTrue($reflection->isProtected());
        $this->assertCount(0, $reflection->getParameters());
    }

    public function testConfigMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'config');

        $this->assertSame('array', $reflection->getReturnType()->getName());
    }

    public function testCollectionMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'collection');

        $this->assertSame('string', $reflection->getReturnType()->getName());
    }

    // --- 方法返回类型测试 ---

    public function testNowMethodReturnType(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'now');

        $this->assertSame('MongoDB\BSON\UTCDateTime', $reflection->getReturnType()->getName());
    }

    public function testCollectionUsesExplicitCollections(): void
    {
        $model = new TestModel();
        $reflection = new ReflectionClass(TestModel::class);
        $method = $reflection->getMethod('collection');
        $method->setAccessible(true);

        $this->assertSame('tests', $method->invoke($model));
    }

    public function testCollectionAutoGeneratesFromClassName(): void
    {
        $model = new Session();
        $reflection = new ReflectionClass(Session::class);
        $method = $reflection->getMethod('collection');
        $method->setAccessible(true);

        $this->assertSame('sessions', $method->invoke($model));
    }

    // --- collection() 方法测试 ---

    public function testCollectionPluralizesPerson(): void
    {
        $model = new Person();
        $reflection = new ReflectionClass(Person::class);
        $method = $reflection->getMethod('collection');
        $method->setAccessible(true);

        $this->assertSame('people', $method->invoke($model));
    }

    public function testCollectionPluralizesCategory(): void
    {
        $model = new Category();
        $reflection = new ReflectionClass(Category::class);
        $method = $reflection->getMethod('collection');
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

    // --- config() 方法测试 ---

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

    // --- exists() 方法测试 ---

    public function testExistsMethodExists(): void
    {
        $reflection = new ReflectionMethod(Model::class, 'exists');

        $this->assertTrue($reflection->isProtected());
        $this->assertSame('bool', $reflection->getReturnType()->getName());
    }

    public function testExistsMethodHasOptionalFilterParameter(): void
{
    $reflection = new ReflectionMethod(Model::class, 'exists');
    $parameters = $reflection->getParameters();

    $this->assertCount(1, $parameters);
    $this->assertSame('filter', $parameters[0]->getName());
    $this->assertSame('array', $parameters[0]->getType()->getName());
    $this->assertTrue($parameters[0]->isOptional());
    $this->assertSame([], $parameters[0]->getDefaultValue());
}

public function testExistsReturnsTrueWhenDocumentsExistWithoutFilter(): void
{
    $model = new TestModel();
    $mockCollection = $this->createMock(Collection::class);
    $mockCollection->expects($this->once())
        ->method('countDocuments')
        ->willReturn(5);

    $reflection = new ReflectionProperty(Model::class, 'collection');
    $reflection->setAccessible(true);
    $reflection->setValue($model, $mockCollection);

    $method = new ReflectionMethod(Model::class, 'exists');
    $method->setAccessible(true);

    $this->assertTrue($method->invoke($model));
}

public function testExistsReturnsFalseWhenNoDocumentsWithoutFilter(): void
{
    $model = new TestModel();
    $mockCollection = $this->createMock(Collection::class);
    $mockCollection->expects($this->once())
        ->method('countDocuments')
        ->willReturn(0);

    $reflection = new ReflectionProperty(Model::class, 'collection');
    $reflection->setAccessible(true);
    $reflection->setValue($model, $mockCollection);

    $method = new ReflectionMethod(Model::class, 'exists');
    $method->setAccessible(true);

    $this->assertFalse($method->invoke($model));
}

public function testExistsReturnsTrueWhenDocumentMatchesFilter(): void
{
    $model = new TestModel();
    $mockCollection = $this->createMock(Collection::class);
    $mockCollection->expects($this->once())
        ->method('findOne')
        ->with(
            ['name' => 'test'],
            ['projection' => ['_id' => 1]]
        )
        ->willReturn(['_id' => 'some-id']);

    $reflection = new ReflectionProperty(Model::class, 'collection');
    $reflection->setAccessible(true);
    $reflection->setValue($model, $mockCollection);

    $method = new ReflectionMethod(Model::class, 'exists');
    $method->setAccessible(true);

    $this->assertTrue($method->invoke($model, ['name' => 'test']));
}

public function testExistsReturnsFalseWhenNoDocumentMatchesFilter(): void
{
    $model = new TestModel();
    $mockCollection = $this->createMock(Collection::class);
    $mockCollection->expects($this->once())
        ->method('findOne')
        ->with(
            ['name' => 'nonexistent'],
            ['projection' => ['_id' => 1]]
        )
        ->willReturn(null);

    $reflection = new ReflectionProperty(Model::class, 'collection');
    $reflection->setAccessible(true);
    $reflection->setValue($model, $mockCollection);

    $method = new ReflectionMethod(Model::class, 'exists');
    $method->setAccessible(true);

    $this->assertFalse($method->invoke($model, ['name' => 'nonexistent']));
}

    // --- now() 方法测试 ---

    protected function setUp(): void
    {
        Platform::reset();
    }

    protected function tearDown(): void
    {
        Platform::reset();
    }
}
