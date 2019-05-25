<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/LogLoginTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\LogLogin;
use App\Entity\User;
use App\Utils\Tests\PhpUnitUtil;
use DeviceDetector\DeviceDetector;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use function array_key_exists;
use function in_array;
use function ucfirst;

/**
 * Class LogLoginTest
 *
 * @package App\Tests\Integration\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginTest extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = LogLogin::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array  $meta
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        string $field = null,
        string $type = null,
        array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array  $meta
     */
    public function testThatSetterReturnsInstanceOfEntity(
        string $field = null,
        string $type = null,
        array $meta = null
    ): void {
        static::markTestSkipped('There is not setter in read only entity...');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param string $field
     * @param string $type
     * @param array  $meta
     *
     * @throws Throwable
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
        }

        $request = Request::create('');

        // Parse user agent data with device detector
        $deviceDetector = new DeviceDetector($request->headers->get('User-Agent'));
        $deviceDetector->parse();

        $logRequest = new LogLogin(
            '',
            $request,
            $deviceDetector,
            new User()
        );

        if (!(array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta))) {
            $type = ArrayCollection::class;

            static::assertInstanceOf($type, $logRequest->$getter());
        }

        try {
            if (static::isType($type)) {
                $method = 'assertIs' . ucfirst($type);

                static::$method($logRequest->$getter());
            }
        } /** @noinspection BadExceptionsProcessingInspection */ catch (Exception $error) {
            static::assertInstanceOf($type, $logRequest->$getter());
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        gc_enable();

        static::bootKernel();

        // Store container and entity manager
        $this->testContainer = static::$kernel->getContainer();

        /** @noinspection MissingService */
        $this->entityManager = $this->testContainer->get('doctrine.orm.default_entity_manager');

        $request = Request::create('');

        // Parse user agent data with device detector
        $deviceDetector = new DeviceDetector($request->headers->get('User-Agent'));
        $deviceDetector->parse();

        // Create new entity object
        $this->entity = new $this->entityName('', $request, $deviceDetector, new User());

        $this->repository = $this->entityManager->getRepository($this->entityName);

        unset($deviceDetector);
    }
}
