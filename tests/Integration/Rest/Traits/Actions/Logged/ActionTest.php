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
            ->expects(static::once())
            ->method($trait)
            ->with(...$parameters->getArrayCopy());

        $result = call_user_func_array([$stub, $method], $parameters->getArrayCopy());

        static::assertInstanceOf(Response::class, $result);
    }

    public function dataProviderTestThatTraitCallsExpectedMethod(): array
    {
        static::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Rest/Traits/Actions/Logged/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Rest\\Traits\\Actions\\Logged\\';

        $iterator = function (string $filename) use ($folder, $namespace): array {
            $base = str_replace([$folder, DIRECTORY_SEPARATOR, '.php'], ['', '\\', ''], $filename);
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
