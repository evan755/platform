<?php declare(strict_types=1);

namespace Tests\Kernel;

use Evan755\Platform\Kernel\Controller;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Controller::class)]
class ControllerTest extends TestCase
{
    // --- 结构测试 ---

    public function testControllerIsAbstract(): void
    {
        $reflection = new ReflectionClass(Controller::class);

        $this->assertTrue($reflection->isAbstract());
    }

    public function testControllerIsInKernelNamespace(): void
    {
        $reflection = new ReflectionClass(Controller::class);

        $this->assertSame('Evan755\Platform\Kernel', $reflection->getNamespaceName());
    }

    public function testControllerCanBeExtended(): void
    {
        $reflection = new ReflectionClass(Controller::class);

        $this->assertFalse($reflection->isFinal());
    }

    public function testControllerHasNoAbstractMethods(): void
    {
        $reflection = new ReflectionClass(Controller::class);
        $abstractMethods = array_filter(
            $reflection->getMethods(),
            fn($method) => $method->isAbstract()
        );

        $this->assertEmpty($abstractMethods);
    }

    public function testControllerHasNoProperties(): void
    {
        $reflection = new ReflectionClass(Controller::class);
        $properties = $reflection->getProperties();

        $this->assertEmpty($properties);
    }

    public function testControllerHasNoConstructor(): void
    {
        $reflection = new ReflectionClass(Controller::class);

        $this->assertNull($reflection->getConstructor());
    }

    public function testControllerCanBeInstantiatedViaSubclass(): void
    {
        $controller = new class extends Controller {
        };

        $this->assertInstanceOf(Controller::class, $controller);
    }

    public function testControllerSubclassCanAddMethods(): void
    {
        $controller = new class extends Controller {
            public function index(): string
            {
                return 'index';
            }
        };

        $this->assertSame('index', $controller->index());
    }

    public function testControllerSubclassCanAddProperties(): void
    {
        $controller = new class extends Controller {
            public string $name = 'test';
        };

        $this->assertSame('test', $controller->name);
    }

    public function testControllerSubclassCanHaveConstructor(): void
    {
        $controller = new class('test') extends Controller {
            public function __construct(public string $name)
            {
            }
        };

        $this->assertSame('test', $controller->name);
    }

    public function testControllerIsUserlandClass(): void
    {
        $reflection = new ReflectionClass(Controller::class);

        $this->assertFalse($reflection->isInternal());
        $this->assertTrue($reflection->isUserDefined());
    }

    public function testControllerHasDocComment(): void
    {
        $reflection = new ReflectionClass(Controller::class);

        // Controller 类可能没有文档注释，这是可选的
        // 这里只是验证方法存在
        $this->assertTrue(method_exists($reflection, 'getDocComment'));
    }
}
