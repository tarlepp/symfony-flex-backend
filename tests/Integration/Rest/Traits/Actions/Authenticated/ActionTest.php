<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Actions/Authenticated/ActionTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Rest\Traits\Actions\Authenticated;

use App\DTO\RestDtoInterface;
use App\Utils\Tests\PhpUnitUtil;
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
 * @package App\Tests\Integration\Rest\Traits\Actions\Authenticated
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ActionTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatTraitCallsExpectedMethod
     *
     * @throws Throwable
     *
     * @testdox Test that `$method` method call trigger `$traitMethod` method call in `$className` trait.
     */
    public function testThatTraitCallsExpectedMethod(
        string $className,
        string $method,
        string $traitMethod,
        array $parameters
    ): void {
        $stub = $this->getMockForTrait(
            $className,
            [],
            '',
            true,
            true,
            true,
            [$traitMethod]
        );

        $stub
            ->expects(static::once())
            ->method($traitMethod)
            ->with(...$parameters);

        $result = call_user_func_array([$stub, $method], $parameters);

        static::assertInstanceOf(Response::class, $result);
    }

    public function dataProviderTestThatTraitCallsExpectedMethod(): array
    {
        static::bootKernel();

        $folder = static::$kernel->getProjectDir() . '/src/Rest/Traits/Actions/Authenticated/';
        $pattern = '/^.+\.php$/i';

        $namespace = '\\App\\Rest\\Traits\\Actions\\Authenticated\\';

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
                $parameters,
            ];
        };

        return array_map($iterator, PhpUnitUtil::recursiveFileSearch($folder, $pattern));
    }
}
