<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Actions/Logged/ActionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Actions\Logged;

use App\DTO\RestDtoInterface;
use App\Utils\Tests\PhpUnitUtil;
use App\Utils\Tests\StringableArrayObject;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use function array_map;
use function call_user_func_array;
use function lcfirst;
use function str_replace;
use const DIRECTORY_SEPARATOR;

/**
 * Class ActionTest
 *
 * @package App\Tests\Integration\Rest\Traits\Actions\Logged
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ActionTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatTraitCallsExpectedMethod
     *
     * @phpstan-param StringableArrayObject<array<mixed>> $parameters
     * @psalm-param StringableArrayObject $parameters
     * @psalm-param trait-string $class
     *
     * @throws Throwable
     *
     * @testdox Test that `$method` triggers `$trait` method call in `$class` trait when using `$parameters` parameters
     */
    public function testThatTraitCallsExpectedMethod(
        string $class,
        string $method,
        string $trait,
        StringableArrayObject $parameters
    ): void {
        $stub = $this->getMockForTrait(
            $class,
            [],
            '',
            true,
            true,
            true,
            [$trait]
        );

        $stub
            ->expects(self::once())
            ->method($trait)
            ->with(...$parameters->getArrayCopy());

        /** @var callable $callback */
        $callback = [$stub, $method];

        $result = call_user_func_array($callback, $parameters->getArrayCopy());

        self::assertInstanceOf(Response::class, $result);
    }

    /**
     * @psalm-return array<int, array{0: trait-string, 1: string, 2: string, 3: StringableArrayObject}>
     * @phpstan-return array<int, array{0: trait-string, 1: string, 2: string, 3: StringableArrayObject<mixed>}|mixed>
     */
    public function dataProviderTestThatTraitCallsExpectedMethod(): array
    {
        self::bootKernel();

        $folder = self::$kernel->getProjectDir() . '/src/Rest/Traits/Actions/Logged/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Rest\\Traits\\Actions\\Logged\\';

        $iterator = function (string $filename) use ($folder, $namespace): array {
            $base = str_replace([$folder, DIRECTORY_SEPARATOR, '.php'], ['', '\\', ''], $filename);

            /** @psalm-var trait-string $class */
            $class = $namespace . $base;

            $parameters = [
                $request = $this->createMock(Request::class),
            ];

            switch ($base) {
                case 'CreateAction':
                    $parameters[] = $this->createMock(RestDtoInterface::class);
                    break;
                case 'PatchAction':
                case 'UpdateAction':
                    $parameters[] = $this->createMock(RestDtoInterface::class);
                    $parameters[] = Uuid::uuid4()->toString();
                    break;
                case 'DeleteAction':
                case 'FindOneAction':
                    $parameters[] = Uuid::uuid4()->toString();
                    break;
            }

            return [
                $class,
                lcfirst($base),
                str_replace('Action', 'Method', lcfirst($base)),
                new StringableArrayObject($parameters),
            ];
        };

        return array_map($iterator, PhpUnitUtil::recursiveFileSearch($folder, $pattern));
    }
}
