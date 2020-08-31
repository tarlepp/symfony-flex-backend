<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/EntityTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role;
use App\Rest\UuidHelper;
use App\Utils\Tests\PhpUnitUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Throwable;
use TypeError;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function call_user_func;
use function get_class;
use function gettype;
use function in_array;
use function is_object;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * Class EntityTestCase
 *
 * @package App\Tests\Helpers
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class EntityTestCase extends KernelTestCase
{
    protected string $entityName;
    protected EntityInterface $entity;
    protected EntityManager $entityManager;
    protected ContainerInterface $testContainer;
    protected EntityRepository $repository;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        // Store container and entity manager
        $this->testContainer = static::$kernel->getContainer();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        /* @noinspection MissingService */
        $this->entityManager = $this->testContainer->get('doctrine.orm.default_entity_manager');

        // Create new entity object
        $this->entity = $this->getEntity();

        /* @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->repository = $this->entityManager->getRepository($this->entityName);
    }

    /**
     * Method to test that getId() method exists on entity
     */
    public function testThatGetIdMethodExists(): void
    {
        static::assertTrue(
            method_exists($this->entity, 'getId'),
            sprintf(
                "Entity '%s' does not have expected getter 'getId()' method for 'id' property.",
                $this->entityName
            )
        );
    }

    public function testThatGetUuidMethodReturnsExpected(): void
    {
        if (!method_exists($this->entity, 'getUuid')) {
            static::markTestSkipped('Cannot test because `getUuid` method does not exist.');

            return;
        }

        static::assertSame($this->entity->getUuid()->toString(), $this->entity->getId());
    }

    /**
     * Generic method to test that getId method return expected UUID.
     */
    public function testThatGetIdReturnsCorrectUuid(): void
    {
        // Get entity UUID/ID
        $id = $this->entity->getId();

        $factory = UuidHelper::getFactory();

        static::assertSame($id, $factory->fromString($id)->toString());
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @testdox Test that `getter` and `setter` methods exists for `$field` field.
     */
    public function testThatGetterAndSetterExists(string $field, string $type, array $meta, bool $readOnly): void
    {
        $getter = 'get' . ucfirst($field);
        $setter = 'set' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
        }

        static::assertTrue(
            method_exists($this->entity, $getter),
            sprintf(
                "Entity '%s' does not have expected getter '%s()' method for '%s' property.",
                $this->entityName,
                $getter,
                $field
            )
        );

        if (array_key_exists('columnName', $meta)) {
            $message = $readOnly
                ? "Entity '%s' has not expected setter '%s()' method for '%s' property."
                : "Entity '%s' does not have expected setter '%s()' method for '%s' property.";

            static::assertSame(
                !$readOnly,
                method_exists($this->entity, $setter),
                sprintf($message, $this->entityName, $setter, $field)
            );
        }
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @throws Throwable
     *
     * @testdox Test that `setter` method for `$field` field only accepts `$type` parameter.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        string $field,
        string $type,
        array $meta
    ): void {
        $setter = 'set' . ucfirst($field);

        if (!array_key_exists('columnName', $meta) && !array_key_exists('joinColumns', $meta)) {
            static::markTestSkipped('No need to test this setter...');
        }

        $this->expectException(TypeError::class);

        $value = PhpUnitUtil::getInvalidValueForType($type);

        $this->entity->{$setter}($value);

        $message = sprintf(
            "Setter '%s' didn't fail with invalid value type '%s', maybe missing variable type?",
            $setter,
            is_object($value) ? gettype($value) : '(' . gettype($value) . ')' . $value
        );

        static::fail($message);
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @throws Throwable
     *
     * @testdox Test that `setter` method for `$field` field is fluent.
     */
    public function testThatSetterReturnsInstanceOfEntity(
        string $field,
        string $type,
        array $meta
    ): void {
        $setter = 'set' . ucfirst($field);

        if (!array_key_exists('columnName', $meta)) {
            static::markTestSkipped('No need to test this setter...');
        }

        static::assertInstanceOf(
            get_class($this->entity),
            call_user_func([$this->entity, $setter], PhpUnitUtil::getValidValueForType($type, $meta)),
            sprintf(
                "Entity '%s' setter '%s()' method for '%s' property did not return expected value.",
                $this->entityName,
                $setter,
                $field
            )
        );
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @throws Throwable
     *
     * @testdox Test that `getter` method for `$field` field returns value of type `$type`.
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . ucfirst($field);
        $setter = 'set' . ucfirst($field);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($field);
        }

        if (array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta)) {
            $value = PhpUnitUtil::getValidValueForType($type, $meta);

            $this->entity->{$setter}($value);

            static::assertSame(
                $value,
                call_user_func([$this->entity, $getter]),
                sprintf(
                    'Getter method of %s:%s did not return expected value type (%s) and it returned (%s)',
                    $this->entityName,
                    $getter,
                    gettype($value),
                    gettype(call_user_func([$this->entity, $getter]))
                )
            );
        } else {
            $type = ArrayCollection::class;

            static::assertInstanceOf(
                $type,
                call_user_func([$this->entity, $getter])
            );
        }

        try {
            if (static::isType($type)) {
                $method = 'assertIs' . ucfirst($type);

                static::$method($this->entity->{$getter}());
            }
        } catch (Throwable $error) {
            static::assertInstanceOf($type, call_user_func([$this->entity, $getter]), $error->getMessage());
        }
    }

    /**
     * @dataProvider dataProviderTestThatAssociationMethodsExists
     *
     * @param mixed $input
     * @param bool|string $output
     *
     * @testdox Test that association method `$method` exist for `$field` and it returns `$output` when using `$input`.
     */
    public function testThatAssociationMethodsExistsAndThoseReturnsCorrectValue(
        string $method,
        string $field,
        $input,
        $output
    ): void {
        if ($method === '') {
            static::markTestSkipped("Entity doesn't have associations, so cannot test those...");
        }

        static::assertTrue(
            method_exists($this->entity, $method),
            sprintf(
                "Entity '%s' does not have expected association method '%s()' for property '%s'.",
                $this->entityName,
                $method,
                $field
            )
        );

        if ($output) {
            static::assertInstanceOf($output, call_user_func([$this->entity, $method], $input));
        }
    }

    /**
     * @dataProvider dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected
     *
     * @param string|bool $getter
     * @param string|bool $adder
     * @param string|bool $removal
     * @param string|bool $clear
     * @param string|bool $field
     * @param string|bool $entity
     *
     * @testdox Test that `many-to-many` assoc methods `$getter, $adder, $removal, $clear` works for `$field + $entity`.
     */
    public function testThatManyToManyAssociationMethodsWorksAsExpected(
        $getter,
        $adder,
        $removal,
        $clear,
        $field,
        $entity,
        array $mappings
    ): void {
        if ($getter === false) {
            static::markTestSkipped('Entity does not contain many-to-many relationships.');

            return;
        }

        static::assertInstanceOf(
            get_class($this->entity),
            call_user_func([$this->entity, $adder], $entity),
            sprintf(
                "Added method '%s()' for property '%s' did not return instance of the entity itself",
                $adder,
                $field
            )
        );

        /** @var ArrayCollection $collection */
        $collection = call_user_func([$this->entity, $getter]);

        static::assertTrue(
            $collection->contains($entity)
        );

        if (isset($mappings['mappedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $entity->{'get' . ucfirst($mappings['mappedBy'])}();

            static::assertTrue($collection->contains($this->entity));
        } elseif (isset($mappings['inversedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $entity->{'get' . ucfirst($mappings['inversedBy'])}();

            static::assertTrue($collection->contains($this->entity));
        }

        static::assertInstanceOf(
            get_class($this->entity),
            call_user_func([$this->entity, $removal], $entity),
            sprintf(
                "Removal method '%s()' for property '%s' did not return instance of the entity itself",
                $adder,
                $field
            )
        );

        /** @var ArrayCollection $collection */
        $collection = call_user_func([$this->entity, $getter]);

        static::assertTrue($collection->isEmpty());

        if (isset($mappings['mappedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $entity->{'get' . ucfirst($mappings['mappedBy'])}();

            static::assertTrue($collection->isEmpty());
        } elseif (isset($mappings['inversedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $entity->{'get' . ucfirst($mappings['inversedBy'])}();

            static::assertTrue($collection->isEmpty());
        }

        // Test for 'clear' method

        $this->entity->{$adder}($entity);

        static::assertInstanceOf(
            get_class($this->entity),
            call_user_func([$this->entity, $clear]),
            sprintf(
                "Clear method '%s()' for property '%s' did not return instance of the entity itself",
                $adder,
                $field
            )
        );

        /** @var ArrayCollection $collection */
        $collection = call_user_func([$this->entity, $getter]);

        static::assertTrue($collection->isEmpty());
    }

    /**
     * @dataProvider dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected
     *
     * @param string|bool $setter
     * @param string|bool $getter
     * @param string|bool $targetEntity
     * @param string|bool $field
     *
     * @testdox Test that `ManyToOne` assoc methods `$getter` and `$setter` works for `$field + $targetEntity`.
     */
    public function testThatManyToOneAssociationMethodsWorksAsExpected(
        $setter,
        $getter,
        $targetEntity,
        $field
    ): void {
        if ($setter === false) {
            static::markTestSkipped('Entity does not contain many-to-one relationships.');
        }

        static::assertInstanceOf(
            get_class($this->entity),
            call_user_func([$this->entity, $setter], $targetEntity),
            sprintf(
                "Setter method '%s()' for property '%s' did not return instance of the entity itself",
                $setter,
                $field
            )
        );

        static::assertInstanceOf(
            get_class($targetEntity),
            call_user_func([$this->entity, $getter]),
            sprintf(
                "Getter method '%s()' for property '%s' did not return expected object '%s'.",
                $getter,
                $field,
                get_class($targetEntity)
            )
        );
    }

    /**
     * @dataProvider dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected
     *
     * @param string|bool $methodGetter
     * @param string|bool $field
     *
     * @testdox Test that `$methodGetter` method works as expected for `$field` field.
     */
    public function testThatOneToManyAssociationMethodsWorksAsExpected($methodGetter, $field): void
    {
        if ($methodGetter === false) {
            static::markTestSkipped('Entity does not contain one-to-many relationships.');
        }

        static::assertInstanceOf(
            ArrayCollection::class,
            call_user_func([$this->entity, $methodGetter]),
            sprintf(
                "Getter method '%s()' for property '%s' did not return expected 'ArrayCollection' object.",
                $methodGetter,
                $field
            )
        );
    }

    /**
     * Generic data provider for following common entity tests:
     *  - testThatGetterAndSetterExists
     *  - testThatSetterReturnsInstanceOfEntity
     *  - testThatGetterReturnsExpectedValue
     */
    public function dataProviderTestThatSetterAndGettersWorks(): array
    {
        static::bootKernel();

        /** @noinspection MissingService */
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        /**
         * Lambda function to generate actual test case arrays for tests. Output value is an array which contains
         * following data:
         *  1) Name
         *  2) Type
         *  4) meta
         *
         * @param string $field
         *
         * @return array
         *
         * @throws Throwable
         */
        $iterator = fn (string $field): array => [
            $field,
            PhpUnitUtil::getType($meta->getTypeOfField($field)),
            $meta->getFieldMapping($field),
            $meta->isReadOnly,
        ];

        $fieldsToOmit = array_merge(
            $meta->getIdentifierFieldNames(),
            ['password']
        );

        $entityManager->close();

        $assocFields = [];

        foreach ($meta->getAssociationMappings() as $mapping) {
            if (in_array($mapping['fieldName'], ['createdBy', 'updatedBy', 'deletedBy'], true)) {
                continue;
            }

            $field = $mapping['fieldName'];
            $type = $mapping['targetEntity'];

            $assocFields[] = [$field, $type, $mapping, $meta->isReadOnly];
        }

        return array_merge(
            array_map(
                $iterator,
                array_filter(
                    $meta->getFieldNames(),
                    fn (string $field): bool => !in_array($field, $fieldsToOmit, true)
                )
            ),
            $assocFields
        );
    }

    public function dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected(): array
    {
        static::bootKernel();

        /** @noinspection MissingService */
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = static function (array $mapping): array {
            $targetEntity = new $mapping['targetEntity']();

            $singular = $mapping['fieldName'][mb_strlen($mapping['fieldName']) - 1] === 's' ?
                mb_substr($mapping['fieldName'], 0, -1) : $mapping['fieldName'];

            return [
                [
                    'get' . ucfirst($mapping['fieldName']),
                    'add' . ucfirst($singular),
                    'remove' . ucfirst($singular),
                    'clear' . ucfirst($mapping['fieldName']),
                    $mapping['fieldName'],
                    $targetEntity,
                    $mapping,
                ],
            ];
        };

        $entityManager->close();

        static::$kernel->shutdown();

        $items = array_filter(
            $meta->getAssociationMappings(),
            fn ($mapping): bool => $mapping['type'] === ClassMetadataInfo::MANY_TO_MANY
        );

        if (empty($items)) {
            $output = [
                [false, false, false, false, false, false, []],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    public function dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected(): array
    {
        static::bootKernel();

        /** @noinspection MissingService */
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = static function (array $mapping) use ($meta): array {
            $params = [null];

            if ($mapping['targetEntity'] === Role::class) {
                $params = ['Some Role'];
            }

            $targetEntity = new $mapping['targetEntity'](...$params);

            return [
                [
                    $meta->isReadOnly ? false : 'set' . ucfirst($mapping['fieldName']),
                    'get' . ucfirst($mapping['fieldName']),
                    $targetEntity,
                    $mapping['fieldName'],
                    $mapping,
                ],
            ];
        };

        $entityManager->close();

        static::$kernel->shutdown();

        $items = array_filter(
            $meta->getAssociationMappings(),
            fn (array $mapping): bool => $mapping['type'] === ClassMetadataInfo::MANY_TO_ONE
        );

        if (empty($items)) {
            $output = [
                [false, false, false, false, []],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    public function dataProviderTestThatAssociationMethodsExists(): array
    {
        static::bootKernel();

        /** @noinspection MissingService */
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = function (array $mapping) use ($meta): array {
            $input = $this->createMock($mapping['targetEntity']);

            $methods = [
                ['get' . ucfirst($mapping['fieldName']), $mapping['fieldName'], false, false],
            ];

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::ONE_TO_ONE:
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                    if ($meta->isReadOnly === false) {
                        $methods[] = [
                            'set' . ucfirst($mapping['fieldName']),
                            $mapping['fieldName'],
                            $input,
                            $this->entityName,
                        ];
                    }
                    break;
                case ClassMetadataInfo::MANY_TO_MANY:
                    $singular = $mapping['fieldName'][mb_strlen($mapping['fieldName']) - 1] === 's' ?
                        mb_substr($mapping['fieldName'], 0, -1) : $mapping['fieldName'];

                    $methods = [
                        [
                            'get' . ucfirst($mapping['fieldName']),
                            $mapping['fieldName'],
                            $input,
                            ArrayCollection::class,
                        ],
                    ];

                    if ($meta->isReadOnly === false) {
                        $setters = [
                            [
                                'add' . ucfirst($singular),
                                $mapping['fieldName'],
                                $input,
                                $this->entityName,
                            ],
                            [
                                'remove' . ucfirst($singular),
                                $mapping['fieldName'],
                                $input,
                                $this->entityName,
                            ],
                            [
                                'clear' . ucfirst($mapping['fieldName']),
                                $mapping['fieldName'],
                                $input,
                                $this->entityName,
                            ],
                        ];

                        $methods = array_merge($methods, $setters);
                    }
                    break;
            }

            return $methods;
        };

        $entityManager->close();

        static::$kernel->shutdown();

        // These isn't associations, so return special values that marks test skipped
        if (empty($meta->getAssociationMappings())) {
            $output = [
                ['', '', null, null],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $meta->getAssociationMappings())));
        }

        return $output;
    }

    public function dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected(): array
    {
        static::bootKernel();

        /** @noinspection MissingService */
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = fn (array $mapping): array => [
            [
                'get' . ucfirst($mapping['fieldName']),
                $mapping['fieldName'],
                $mapping,
            ],
        ];

        $entityManager->close();

        static::$kernel->shutdown();

        $items = array_filter(
            $meta->getAssociationMappings(),
            fn (array $mapping): bool => $mapping['type'] === ClassMetadataInfo::ONE_TO_MANY
        );

        if (empty($items)) {
            $output = [
                [false, false, []],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    protected function getEntity(): EntityInterface
    {
        return new $this->entityName();
    }
}
