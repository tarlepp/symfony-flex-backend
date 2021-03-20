<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/LogLoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\LogLogin;
use App\Entity\User;
use App\Utils\Tests\PhpUnitUtil;
use DeviceDetector\DeviceDetector;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use function array_key_exists;
use function in_array;
use function ucfirst;

/**
 * Class LogLoginTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 *
 * @method LogLogin getEntity()
 */
class LogLoginTest extends EntityTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName = LogLogin::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @testdox No setter for `$field` field in read only entity - so cannot test this.
     */
    public function testThatSetterReturnsInstanceOfEntity(
        ?string $field = null,
        ?string $type = null,
        ?array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @throws Throwable
     *
     * @testdox Test that getter method for `$field` with `$type` returns expected.
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
        }

        $request = Request::create('');

        // Parse user agent data with device detector
        $deviceDetector = new DeviceDetector((string)$request->headers->get('User-Agent'));
        $deviceDetector->parse();

        $logRequest = new LogLogin(
            '',
            $request,
            $deviceDetector,
            new User()
        );

        if (!(array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta))) {
            $type = ArrayCollection::class;

            static::assertInstanceOf($type, $logRequest->{$getter}());
        }

        try {
            $method = 'assertIs' . ucfirst($type);

            static::$method($logRequest->{$getter}());
        } catch (Throwable $error) {
            /**
             * @var class-string $type
             */
            static::assertInstanceOf($type, $logRequest->{$getter}(), $error->getMessage());
        }
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    protected function createEntity(): LogLogin
    {
        $request = Request::create('');

        // Parse user agent data with device detector
        $deviceDetector = new DeviceDetector((string)$request->headers->get('User-Agent'));
        $deviceDetector->parse();

        return new LogLogin('', $request, $deviceDetector, new User());
    }
}
