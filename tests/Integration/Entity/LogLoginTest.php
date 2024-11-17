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
use App\Enum\LogLogin as LogLoginEnum;
use App\Tests\Integration\TestCase\EntityTestCase;
use App\Tests\Utils\PhpUnitUtil;
use DeviceDetector\DeviceDetector;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\FieldMapping;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use function in_array;
use function method_exists;
use function ucfirst;

/**
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
    protected static string $entityName = LogLogin::class;

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterOnlyAcceptSpecifiedType(
        string $property,
        string $type,
        FieldMapping|AssociationMapping $meta,
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    #[TestDox('No setter for `$property` property in read only entity - so cannot test this')]
    #[Override]
    public function testThatSetterReturnsInstanceOfEntity(
        string $property,
        string $type,
        FieldMapping|AssociationMapping $meta,
    ): void {
        self::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    #[TestDox('Test that getter method for `$type $property` property returns expected')]
    #[Override]
    public function testThatGetterReturnsExpectedValue(
        string $property,
        string $type,
        FieldMapping|AssociationMapping $meta,
    ): void {
        $getter = 'get' . ucfirst($property);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($property);
        }

        $request = Request::create('');

        // Parse user agent data with device detector
        $deviceDetector = new DeviceDetector((string)$request->headers->get('User-Agent'));
        $deviceDetector->parse();

        $logRequest = new LogLogin(
            LogLoginEnum::SUCCESS,
            $request,
            $deviceDetector,
            new User(),
        );

        if ($meta instanceof AssociationMapping
            && (
                $meta->isManyToManyOwningSide()
                || $meta->isOneToMany()
            )
        ) {
            $type = ArrayCollection::class;

            self::assertInstanceOf($type, $logRequest->{$getter}());
        }

        try {
            $method = 'assertIs' . ucfirst($type);

            self::$method($logRequest->{$getter}());
        } catch (Throwable $error) {
            /**
             * @var class-string $type
             */
            self::assertInstanceOf($type, $logRequest->{$getter}(), $error->getMessage());
        }
    }

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     *
     * @throws Throwable
     */
    #[Override]
    protected function createEntity(): LogLogin
    {
        $request = Request::create('');

        // Parse user agent data with device detector
        $deviceDetector = new DeviceDetector((string)$request->headers->get('User-Agent'));
        $deviceDetector->parse();

        return new LogLogin(LogLoginEnum::SUCCESS, $request, $deviceDetector, new User());
    }
}
