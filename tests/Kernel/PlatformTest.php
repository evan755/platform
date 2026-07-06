<?php declare(strict_types=1);

namespace Tests\Kernel;

use Composer\Autoload\ClassLoader;
use DirectoryIterator;
use Evan755\Platform\Kernel\Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use stdClass;

#[CoversClass(Platform::class)]
class PlatformTest extends TestCase
{
    protected string $appsDir;

    protected function setUp(): void
    {
        $rootDir = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3);
        $this->appsDir = $rootDir . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($this->appsDir)) {
            mkdir($this->appsDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        Platform::reset();

        if (is_dir($this->appsDir)) {
            $iterator = new DirectoryIterator($this->appsDir);
            foreach ($iterator as $item) {
                if ($item->isDot()) {
                    continue;
                }
                $appJson = $item->getPathname() . DIRECTORY_SEPARATOR . 'App.json';
                if (file_exists($appJson)) {
                    unlink($appJson);
                }
                if ($item->isDir()) {
                    rmdir($item->getPathname());
                } elseif ($item->isFile()) {
                    unlink($item->getPathname());
                }
            }
            rmdir($this->appsDir);
        }
    }

    // --- getInstance 测试 ---

    public function testGetInstanceReturnsPlatformInstance(): void
    {
        $platform = Platform::getInstance();

        $this->assertInstanceOf(Platform::class, $platform);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $first = Platform::getInstance();
        $second = Platform::getInstance();

        $this->assertSame($first, $second);
    }

    public function testSingletonSurvivesMultipleGetInstanceCalls(): void
    {
        $instances = [];
        for ($i = 0; $i < 5; $i++) {
            $instances[] = Platform::getInstance();
        }

        for ($i = 1; $i < count($instances); $i++) {
            $this->assertSame($instances[0], $instances[$i]);
        }
    }

    // --- reset 测试 ---

    public function testResetIsPublicStatic(): void
    {
        $method = new ReflectionMethod(Platform::class, 'reset');

        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    public function testResetClearsSingleton(): void
    {
        $first = Platform::getInstance();
        Platform::reset();
        $second = Platform::getInstance();

        $this->assertNotSame($first, $second);
    }

    public function testResetAllowsFreshInstance(): void
    {
        $platform = Platform::getInstance();
        $oldRuntime = $platform->runtime;

        Platform::reset();
        $fresh = Platform::getInstance();

        $this->assertInstanceOf(Platform::class, $fresh);
        $this->assertSame($oldRuntime, $fresh->runtime);
    }

    // --- 反序列化/克隆防护测试 ---

    public function testConstructorIsProtected(): void
    {
        $constructor = new ReflectionMethod(Platform::class, '__construct');

        $this->assertTrue($constructor->isProtected());
    }

    public function testWakeupMethodExists(): void
    {
        $this->assertTrue(method_exists(Platform::class, '__wakeup'));
    }

    public function testWakeupMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Platform::class, '__wakeup');

        $this->assertTrue($method->isPublic());
    }

    public function testCloneMethodIsPrivate(): void
    {
        $method = new ReflectionMethod(Platform::class, '__clone');

        $this->assertTrue($method->isPrivate());
    }

    public function testWakeupPreventsDeserializationExploit(): void
    {
        // __wakeup 为空，反序列化不会创建新的有效实例
        $serialized = serialize(Platform::getInstance());
        Platform::reset();

        $unserialized = unserialize($serialized);

        // __wakeup 被调用但不初始化属性，反序列化的对象属性未设置
        $this->assertInstanceOf(Platform::class, $unserialized);
        // 验证单例仍为 null（反序列化不影响单例状态）
        $fresh = Platform::getInstance();
        $this->assertNotSame($unserialized, $fresh);
    }

    // --- name / version 测试 ---

    public function testNamePropertyIsPublic(): void
    {
        $reflection = new ReflectionProperty(Platform::class, 'name');

        $this->assertTrue($reflection->isPublic());
    }

    public function testVersionPropertyIsPublic(): void
    {
        $reflection = new ReflectionProperty(Platform::class, 'version');

        $this->assertTrue($reflection->isPublic());
    }

    public function testDefaultNameWhenNoComposerJson(): void
    {
        $platform = Platform::getInstance();

        $this->assertSame('Platform', $platform->name);
    }

    public function testDefaultVersionWhenNoComposerJson(): void
    {
        $platform = Platform::getInstance();

        $this->assertSame('1.0.0', $platform->version);
    }

    public function testNameFromComposerJson(): void
    {
        file_put_contents($this->appsDir . DIRECTORY_SEPARATOR . 'composer.json', json_encode([
            'name' => 'my-app/platform',
            'version' => '2.5.0',
        ]));

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertSame('my-app/platform', $platform->name);
    }

    public function testVersionFromComposerJson(): void
    {
        file_put_contents($this->appsDir . DIRECTORY_SEPARATOR . 'composer.json', json_encode([
            'name' => 'my-app/platform',
            'version' => '2.5.0',
        ]));

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertSame('2.5.0', $platform->version);
    }

    public function testNameDefaultsWhenComposerJsonMissingName(): void
    {
        file_put_contents($this->appsDir . DIRECTORY_SEPARATOR . 'composer.json', json_encode([
            'version' => '3.0.0',
        ]));

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertSame('Platform', $platform->name);
    }

    public function testVersionDefaultsWhenComposerJsonMissingVersion(): void
    {
        file_put_contents($this->appsDir . DIRECTORY_SEPARATOR . 'composer.json', json_encode([
            'name' => 'my-app/platform',
        ]));

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertSame('1.0.0', $platform->version);
    }

    public function testNameDefaultsWhenComposerJsonInvalid(): void
    {
        file_put_contents($this->appsDir . DIRECTORY_SEPARATOR . 'composer.json', '{invalid json}');

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertSame('Platform', $platform->name);
        $this->assertSame('1.0.0', $platform->version);
    }

    // --- composer 方法测试 ---

    public function testComposerMethodIsProtected(): void
    {
        $method = new ReflectionMethod(Platform::class, 'composer');

        $this->assertTrue($method->isProtected());
    }

    public function testComposerReturnsEmptyArrayWhenFileMissing(): void
    {
        $method = new ReflectionMethod(Platform::class, 'composer');

        $platform = Platform::getInstance();
        $result = $method->invoke($platform);

        $this->assertSame([], $result);
    }

    public function testComposerReturnsParsedJson(): void
    {
        $data = ['name' => 'test/app', 'version' => '1.0.0'];
        file_put_contents($this->appsDir . DIRECTORY_SEPARATOR . 'composer.json', json_encode($data));

        Platform::reset();
        $method = new ReflectionMethod(Platform::class, 'composer');
        $platform = Platform::getInstance();
        $result = $method->invoke($platform);

        $this->assertSame($data, $result);
    }

    public function testComposerReturnsEmptyArrayForInvalidJson(): void
    {
        file_put_contents($this->appsDir . DIRECTORY_SEPARATOR . 'composer.json', '{bad}');

        Platform::reset();
        $method = new ReflectionMethod(Platform::class, 'composer');
        $platform = Platform::getInstance();
        $result = $method->invoke($platform);

        $this->assertSame([], $result);
    }

    // --- 属性存在性测试 ---

    public function testInstancePropertyIsProtected(): void
    {
        $reflection = new ReflectionProperty(Platform::class, 'instance');

        $this->assertTrue($reflection->isProtected());
    }

    public function testInstancePropertyIsNullablePlatform(): void
    {
        $reflection = new ReflectionProperty(Platform::class, 'instance');

        $this->assertSame('self', $reflection->getType()?->getName());
        $this->assertTrue($reflection->getType()?->allowsNull());
    }

    public function testAllDirectoryPropertiesArePublic(): void
    {
        $reflection = new ReflectionClass(Platform::class);

        foreach (['rootDirectory', 'appsDirectory', 'publicDirectory', 'testsDirectory', 'runtime', 'name', 'version'] as $name) {
            $property = $reflection->getProperty($name);
            $this->assertTrue($property->isPublic(), "Property '$name' should be public.");
        }
    }

    public function testAppsPropertyIsPublic(): void
    {
        $reflection = new ReflectionClass(Platform::class);
        $property = $reflection->getProperty('apps');

        $this->assertTrue($property->isPublic());
    }

    // --- 目录路径测试 ---

    public function testRootDirectoryPropertyIsSet(): void
    {
        $platform = Platform::getInstance();

        $this->assertNotEmpty($platform->rootDirectory);
    }

    public function testRootDirectoryPropertyIsAValidDirectory(): void
    {
        $platform = Platform::getInstance();

        $this->assertDirectoryExists($platform->rootDirectory);
    }

    public function testRootDirectoryPointsToProjectRoot(): void
    {
        $platform = Platform::getInstance();

        $this->assertSame(
            realpath(dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 3)),
            realpath($platform->rootDirectory)
        );
    }

    public function testAppsDirectoryIsSubdirectoryOfRootDirectory(): void
    {
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->rootDirectory . DIRECTORY_SEPARATOR . 'app',
            $platform->appsDirectory
        );
    }

    public function testPublicDirectoryIsSubdirectoryOfRootDirectory(): void
    {
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->rootDirectory . DIRECTORY_SEPARATOR . 'public',
            $platform->publicDirectory
        );
    }

    public function testTestsDirectoryIsSubdirectoryOfRootDirectory(): void
    {
        $platform = Platform::getInstance();

        $this->assertSame(
            $platform->rootDirectory . DIRECTORY_SEPARATOR . 'tests',
            $platform->testsDirectory
        );
    }

    // --- runtime 测试 ---

    public function testRuntimePropertyIsSet(): void
    {
        $platform = Platform::getInstance();

        $this->assertNotEmpty($platform->runtime);
    }

    public function testRuntimeIsCliInCliEnvironment(): void
    {
        $platform = Platform::getInstance();

        if (PHP_SAPI === 'cli') {
            $this->assertSame('cli', $platform->runtime);
        } else {
            $this->assertSame('web', $platform->runtime);
        }
    }

    public function testRuntimeOnlyAllowsCliOrWeb(): void
    {
        $platform = Platform::getInstance();

        $this->assertContains($platform->runtime, ['cli', 'web']);
    }

    // --- discoverApps 测试 ---

    public function testAppsPropertyIsArray(): void
    {
        $platform = Platform::getInstance();

        $this->assertIsArray($platform->apps);
    }

    public function testDiscoverAppsMethodIsProtected(): void
    {
        $reflection = new ReflectionMethod(Platform::class, 'discoverApps');

        $this->assertTrue($reflection->isProtected());
    }

    public function testDiscoverAppsWithAppJson(): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'test-app';
        mkdir($appDir, 0755, true);
        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', json_encode([
            'title' => 'Test Application',
            'version' => '1.0.0',
        ]));

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertArrayHasKey('test-app', $platform->apps);

        $app = $platform->apps['test-app'];
        $this->assertSame('Test Application', $app->title);
        $this->assertSame('1.0.0', $app->version);
        $this->assertSame('test-app', $app->slug);
    }

    public function testDiscoverAppsWithoutAppJson(): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'bare-app';
        mkdir($appDir, 0755, true);

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertArrayHasKey('bare-app', $platform->apps);

        $app = $platform->apps['bare-app'];
        $this->assertInstanceOf(stdClass::class, $app);
        $this->assertSame('bare-app', $app->slug);
    }

    public function testDiscoverAppsSetsSlugAndAppDirectory(): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'my-app';
        mkdir($appDir, 0755, true);

        Platform::reset();
        $platform = Platform::getInstance();

        $app = $platform->apps['my-app'];
        $this->assertSame('my-app', $app->slug);
        $this->assertSame($appDir, $app->appDirectory);
    }

    public function testDiscoverAppsSkipsDotEntries(): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'real-app';
        mkdir($appDir, 0755, true);

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertArrayHasKey('real-app', $platform->apps);
        $this->assertArrayNotHasKey('.', $platform->apps);
        $this->assertArrayNotHasKey('..', $platform->apps);
    }

    public function testDiscoverAppsHandlesInvalidJson(): void
    {
        $appDir = $this->appsDir . DIRECTORY_SEPARATOR . 'bad-json-app';
        mkdir($appDir, 0755, true);
        file_put_contents($appDir . DIRECTORY_SEPARATOR . 'App.json', '{invalid json}');

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertArrayHasKey('bad-json-app', $platform->apps);
        $this->assertInstanceOf(stdClass::class, $platform->apps['bad-json-app']);
        $this->assertSame('bad-json-app', $platform->apps['bad-json-app']->slug);
    }

    public function testDiscoverAppsMultipleApps(): void
    {
        foreach (['app-one', 'app-two', 'app-three'] as $name) {
            mkdir($this->appsDir . DIRECTORY_SEPARATOR . $name, 0755, true);
        }

        Platform::reset();
        $platform = Platform::getInstance();

        $this->assertCount(3, $platform->apps);
        $this->assertArrayHasKey('app-one', $platform->apps);
        $this->assertArrayHasKey('app-two', $platform->apps);
        $this->assertArrayHasKey('app-three', $platform->apps);
    }

    public function testDiscoverAppsCalledDuringConstruction(): void
    {
        $platform = Platform::getInstance();

        $this->assertIsArray($platform->apps);
    }
}
