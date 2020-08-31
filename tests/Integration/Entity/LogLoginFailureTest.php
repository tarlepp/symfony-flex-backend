<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/LogLoginFailureTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\LogLoginFailure;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Throwable;
use function array_key_exists;
use function ucfirst;

/**
 * Class LogLoginFailureTest
 *
 * @package App\Tests\Integration\Entity
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LogLoginFailureTest extends EntityTestCase
{
    protected string $entityName = LogLoginFailure::class;

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        static::bootKernel();

        // Store container and entity manager
        $this->testContainer = static::$kernel->getContainer();

        /* @noinspection MissingService */
        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->entityManager = $this->testContainer->get('doctrine.orm.default_entity_manager');

        // Create new entity object and set repository
        $this->entity = new $this->entityName(new User());

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->repository = $this->entityManager->getRepository($this->entityName);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * @param string $field
     * @param string $type
     * @param array $meta
     *
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
     * @param string $field
     * @param string $type
     * @param array $meta
     *
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

        if ($type === 'boolean') {
            $getter = 'is' . ucfirst($field);
        }

        $logRequest = new LogLoginFailure(
            new User()
        );

        if (!(array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta))) {
            $type = ArrayCollection::class;

            static::assertInstanceOf($type, $logRequest->{$getter}());
        }

        try {
            if (static::isType($type)) {
                $method = 'assertIs' . ucfirst($type);

                static::$method($logRequest->{$getter}());
            }
        } catch (Throwable $error) {
            static::assertInstanceOf($type, $logRequest->{$getter}(), $error->getMessage());
        }
    }
}
