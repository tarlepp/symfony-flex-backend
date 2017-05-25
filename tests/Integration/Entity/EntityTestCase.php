<?php
declare(strict_types=1);
/**
 * /tests/Integration/Entity/EntityTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\EntityInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class EntityTestCase
 *
 * @package App\Tests\Helpers
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
abstract class EntityTestCase extends KernelTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EntityInterface
     */
    protected $entity;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * @param   string  $type
     * @param   array   $meta
     *
     * @return  array|bool|\DateTime|int|string
     */
    private static function getValidValueForType(string $type, array $meta)
    {
        if (\substr_count($type, '\\') > 1) {
            $type = 'CustomClass';
        }

        switch ($type) {
            case 'CustomClass':
                $value = new $meta['targetEntity']();
                break;
            case 'integer':
                $value = 666;
                break;
            case \DateTime::class:
                $value = new \DateTime();
                break;
            case 'string':
                $value = 'Some text here';
                break;
            case 'array':
                $value = ['some', 'array', 'here'];
                break;
            case 'boolean':
                $value = true;
                break;
            default:
                $message = \sprintf(
                    "Cannot create valid value for type '%s'.",
                    $type
                );

                throw new \LogicException($message);
                break;
        }

        return $value;
    }

    /**
     * @param   string $type
     *
     * @return  mixed
     */
    private static function getNotValidValueForType(string $type)
    {
        if (\substr_count($type, '\\') > 1) {
            $type = 'CustomClass';
        }

        switch ($type) {
            case 'CustomClass':
            case 'integer':
            case \DateTime::class:
            case 'string':
            case 'array':
            case 'boolean':
                $value = new \stdClass();
                break;
            default:
                $message = \sprintf(
                    "Cannot create invalid value for type '%s'.",
                    $type
                );

                throw new \LogicException($message);
                break;
        }

        return $value;
    }

    public function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        // Store container and entity manager
        $this->container = static::$kernel->getContainer();
        $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');

        // Create new entity object
        $this->entity = new $this->entityName();

        $this->repository = $this->entityManager->getRepository($this->entityName);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
        $this->entityManager = null; // avoid memory leaks

        self::$kernel->shutdown();
    }

    /**
     * Method to test that getId() method exists on entity
     */
    public function testThatGetIdMethodExists(): void
    {
        static::assertTrue(
            \method_exists($this->entity, 'getId'),
            \sprintf(
                "Entity '%s' does not have expected getter 'getId()' method for 'id' property.",
                $this->entityName
            )
        );
    }

    /**
     * Generic method to test that getId method returns a string and it is UUID V4 format
     */
    public function testThatGetIdReturnsUuidString(): void
    {
        // Get entity UUID
        $uuid = $this->entity->getId();

        // Asserts
        static::assertInternalType('string', $uuid);
        static::assertRegExp('#^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$#', $uuid);
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param string $field
     * @param string $type
     * @param array  $meta
     */
    public function testThatGetterAndSetterExists(string $field, string $type, array $meta): void
    {
        $getter = 'get' . \ucfirst($field);
        $setter = 'set' . \ucfirst($field);

        if ($type === 'boolean') {
            $getter = 'is' . \ucfirst($field);
        }

        static::assertTrue(
            \method_exists($this->entity, $getter),
            \sprintf(
                "Entity '%s' does not have expected getter '%s()' method for '%s' property.",
                $this->entityName,
                $getter,
                $field
            )
        );

        if (\array_key_exists('columnName', $meta)) {
            static::assertTrue(
                \method_exists($this->entity, $setter),
                \sprintf(
                    "Entity '%s' does not have expected setter '%s()' method for '%s' property.",
                    $this->entityName,
                    $setter,
                    $field
                )
            );
        }
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param string $field
     * @param string $type
     * @param array  $meta
     */
    public function testThatSetterOnlyAcceptSpecifiedType(string $field, string $type, array $meta): void
    {
        $setter = 'set' . \ucfirst($field);

        if (!\array_key_exists('columnName', $meta) && !\array_key_exists('joinColumns', $meta)) {
            static::markTestSkipped('No need to test this setter...');
        }

        $this->expectException(\TypeError::class);

        $value = self::getNotValidValueForType($type);

        $this->entity->{$setter}($value);

        $message = \sprintf(
            "Setter '%s' didn't fail with invalid value type '%s', maybe missing variable type?",
            $setter,
            \is_object($value) ? \gettype($value) : '(' . \gettype($value) . ')' . $value
        );

        static::fail($message);
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param string $field
     * @param string $type
     * @param array  $meta
     */
    public function testThatSetterReturnsInstanceOfEntity(string $field, string $type, array $meta): void
    {
        $setter = 'set' . \ucfirst($field);

        if (!\array_key_exists('columnName', $meta)) {
            static::markTestSkipped('No need to test this setter...');
        }

        static::assertInstanceOf(
            \get_class($this->entity),
            \call_user_func([$this->entity, $setter], self::getValidValueForType($type, $meta)),
            \sprintf(
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
     * @param string $field
     * @param string $type
     * @param array  $meta
     */
    public function testThatGetterReturnsExpectedValue(string $field, string $type, array $meta): void
    {
        $getter = 'get' . \ucfirst($field);
        $setter = 'set' . \ucfirst($field);

        if ($type === 'boolean') {
            $getter = 'is' . \ucfirst($field);
        }

        if (\array_key_exists('columnName', $meta) || \array_key_exists('joinColumns', $meta)) {
            $value = self::getValidValueForType($type, $meta);

            $this->entity->{$setter}($value);

            static::assertSame(
                $value,
                \call_user_func([$this->entity, $getter]),
                \sprintf(
                    'Getter method of %s:%s did not return expected value type (%s) and it returned (%s)',
                    $this->entityName,
                    $getter,
                    \gettype($value),
                    \gettype(\call_user_func([$this->entity, $getter]))
                )
            );
        } else {
            $type = ArrayCollection::class;

            static::assertInstanceOf(
                $type,
                \call_user_func([$this->entity, $getter])
            );
        }

        try {
            if (static::isType($type)) {
                static::assertInternalType($type, \call_user_func([$this->entity, $getter]));
            }
        } catch (\Exception $error) {
            static::assertInstanceOf($type, \call_user_func([$this->entity, $getter]));
        }
    }

    /**
     * @dataProvider dataProviderTestThatAssociationMethodsExists
     *
     * @param string         $method
     * @param string         $field
     * @param mixed          $input
     * @param boolean|string $expectedOutput
     */
    public function testThatAssociationMethodsExistsAndThoseReturnsCorrectValue(
        string $method,
        string $field,
        $input,
        $expectedOutput
    ): void
    {
        if ($method === '') {
            self::markTestSkipped("Entity doesn't have associations, so cannot test those...");
        }

        static::assertTrue(
            \method_exists($this->entity, $method),
            \sprintf(
                "Entity '%s' does not have expected association method '%s()' for property '%s'.",
                $this->entityName,
                $method,
                $field
            )
        );

        if ($expectedOutput) {
            static::assertInstanceOf($expectedOutput, \call_user_func([$this->entity, $method], $input));
        }
    }

    /**
     * @dataProvider dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected
     *
     * @param string|boolean $methodGetter
     * @param string         $methodAdder
     * @param string         $methodRemoval
     * @param string         $methodClear
     * @param string         $field
     * @param mixed          $targetEntity
     * @param array          $mappings
     */
    public function testThatManyToManyAssociationMethodsWorksAsExpected(
        $methodGetter,
        string $methodAdder,
        string $methodRemoval,
        string $methodClear,
        string $field,
        $targetEntity,
        array $mappings
    ): void
    {
        if ($methodGetter === false) {
            static::markTestSkipped('Entity does not contain many-to-many relationships.');

            return;
        }

        static::assertInstanceOf(
            \get_class($this->entity),
            \call_user_func([$this->entity, $methodAdder], $targetEntity),
            \sprintf(
                "Added method '%s()' for property '%s' did not return instance of the entity itself",
                $methodAdder,
                $field
            )
        );

        /** @var ArrayCollection $collection */
        $collection = \call_user_func([$this->entity, $methodGetter]);

        static::assertTrue(
            $collection->contains($targetEntity)
        );

        if (isset($mappings['mappedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $targetEntity->{'get' . \ucfirst($mappings['mappedBy'])}();

            static::assertTrue($collection->contains($this->entity));
        } elseif (isset($mappings['inversedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $targetEntity->{'get' . \ucfirst($mappings['inversedBy'])}();

            static::assertTrue($collection->contains($this->entity));
        }

        static::assertInstanceOf(
            \get_class($this->entity),
            \call_user_func([$this->entity, $methodRemoval], $targetEntity),
            \sprintf(
                "Removal method '%s()' for property '%s' did not return instance of the entity itself",
                $methodAdder,
                $field
            )
        );

        /** @var ArrayCollection $collection */
        $collection = \call_user_func([$this->entity, $methodGetter]);

        static::assertTrue($collection->isEmpty());

        if (isset($mappings['mappedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $targetEntity->{'get' . \ucfirst($mappings['mappedBy'])}();

            static::assertTrue($collection->isEmpty());
        } elseif (isset($mappings['inversedBy'])) {
            /** @var ArrayCollection $collection */
            $collection = $targetEntity->{'get' . \ucfirst($mappings['inversedBy'])}();

            static::assertTrue($collection->isEmpty());
        }

        // Test for 'clear' method

        $this->entity->{$methodAdder}($targetEntity);

        static::assertInstanceOf(
            \get_class($this->entity),
            \call_user_func([$this->entity, $methodClear]),
            \sprintf(
                "Clear method '%s()' for property '%s' did not return instance of the entity itself",
                $methodAdder,
                $field
            )
        );

        /** @var ArrayCollection $collection */
        $collection = \call_user_func([$this->entity, $methodGetter]);

        static::assertTrue($collection->isEmpty());
    }

    /**
     * @dataProvider dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected
     *
     * @param string|boolean $methodSetter
     * @param string         $methodGetter
     * @param mixed          $targetEntity
     * @param string         $field
     */
    public function testThatManyToOneAssociationMethodsWorksAsExpected(
        $methodSetter,
        string $methodGetter,
        $targetEntity,
        string $field
    ): void
    {
        if ($methodSetter === false) {
            static::markTestSkipped('Entity does not contain many-to-one relationships.');
        }

        static::assertInstanceOf(
            \get_class($this->entity),
            \call_user_func([$this->entity, $methodSetter], $targetEntity),
            \sprintf(
                "Setter method '%s()' for property '%s' did not return instance of the entity itself",
                $methodSetter,
                $field
            )
        );

        static::assertInstanceOf(
            \get_class($targetEntity),
            \call_user_func([$this->entity, $methodGetter]),
            \sprintf(
                "Getter method '%s()' for property '%s' did not return expected object '%s'.",
                $methodGetter,
                $field,
                \get_class($targetEntity)
            )
        );
    }

    /**
     * @dataProvider dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected
     *
     * @param string|boolean $methodGetter
     * @param string         $field
     */
    public function testThatOneToManyAssociationMethodsWorksAsExpected($methodGetter, string $field): void
    {
        if ($methodGetter === false) {
            static::markTestSkipped('Entity does not contain one-to-many relationships.');
        }

        static::assertInstanceOf(
            ArrayCollection::class,
            \call_user_func([$this->entity, $methodGetter]),
            \sprintf(
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
     *
     * @return array
     */
    public function dataProviderTestThatSetterAndGettersWorks(): array
    {
        self::bootKernel();

        // Get entity manager
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
         */
        $iterator = function (string $field) use ($meta): array {
            $type = $meta->getTypeOfField($field);

            switch ($type) {
                case 'integer':
                case 'bigint':
                    $type = 'integer';
                    break;
                case 'time':
                case 'date':
                case 'datetime':
                    $type = \DateTime::class;
                    break;
                case 'text':
                case 'string':
                    $type = 'string';
                    break;
                case 'array':
                    $type = 'array';
                    break;
                case 'boolean':
                    $type = 'boolean';
                    break;
                default:
                    $message = \sprintf(
                        "Currently type '%s' is not supported within generic EntityTestCase",
                        $type
                    );

                    throw new \LogicException($message);
                    break;
            }

            return [$field, $type, $meta->getFieldMapping($field)];
        };

        $fieldsToOmit = \array_merge(
            $meta->getIdentifierFieldNames(),
            ['password']
        );

        /**
         * Lambda function to filter out all fields that cannot be tested generic
         *
         * @param string $field
         *
         * @return bool
         */
        $filter = function (string $field) use ($fieldsToOmit): bool {
            return !\in_array($field, $fieldsToOmit, true);
        };

        $entityManager->close();
        $entityManager = null; // avoid memory leaks

        $assocFields = [];

        foreach ($meta->getAssociationMappings() as $mapping) {
            if (\in_array($mapping['fieldName'], ['createdBy', 'updatedBy', 'deletedBy'], true)) {
                continue;
            }

            $field = $mapping['fieldName'];
            $type = $mapping['targetEntity'];

            $assocFields[] = [$field, $type, $mapping];
        }

        return \array_merge(
            \array_map($iterator, \array_filter($meta->getFieldNames(), $filter)),
            $assocFields
        );
    }

    /**
     * @return array
     */
    public function dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected(): array
    {
        self::bootKernel();

        // Get entity manager
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = function (array $mapping): array {
            $targetEntity = new $mapping['targetEntity']();

            $singular = $mapping['fieldName'][mb_strlen($mapping['fieldName']) - 1] === 's' ?
                mb_substr($mapping['fieldName'], 0, -1) : $mapping['fieldName'];

            return [
                [
                    'get' . \ucfirst($mapping['fieldName']),
                    'add' . \ucfirst($singular),
                    'remove' . \ucfirst($singular),
                    'clear' . \ucfirst($mapping['fieldName']),
                    $mapping['fieldName'],
                    $targetEntity,
                    $mapping
                ]
            ];
        };

        $filter = function ($mapping) {
            return $mapping['type'] === ClassMetadataInfo::MANY_TO_MANY;
        };

        $entityManager->close();
        $entityManager = null; // avoid memory leaks

        self::$kernel->shutdown();

        $items = \array_filter($meta->getAssociationMappings(), $filter);

        if (empty($items)) {
            return [
                [false, false, false, false, false, false, []]
            ];
        }

        return \array_merge(...\array_values(\array_map($iterator, $items)));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected(): array
    {
        self::bootKernel();

        // Get entity manager
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = function (array $mapping): array {
            $targetEntity = new $mapping['targetEntity']();

            return [
                [
                    'set' . \ucfirst($mapping['fieldName']),
                    'get' . \ucfirst($mapping['fieldName']),
                    $targetEntity,
                    $mapping['fieldName'],
                    $mapping
                ]
            ];
        };

        $filter = function (array $mapping): bool {
            return $mapping['type'] === ClassMetadataInfo::MANY_TO_ONE;
        };

        $entityManager->close();
        $entityManager = null; // avoid memory leaks

        self::$kernel->shutdown();

        $items = \array_filter($meta->getAssociationMappings(), $filter);

        if (empty($items)) {
            return [
                [false, false, false, false, []]
            ];
        }

        return \array_merge(...\array_values(\array_map($iterator, $items)));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatAssociationMethodsExists(): array
    {
        self::bootKernel();

        // Get entity manager
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = function (array $mapping): array {
            $input = new $mapping['targetEntity']();

            $methods = [
                ['get' . \ucfirst($mapping['fieldName']), $mapping['fieldName'], false, false]
            ];

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_ONE:
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                    $methods[] = [
                        'set' . \ucfirst($mapping['fieldName']),
                        $mapping['fieldName'],
                        $input,
                        $this->entityName
                    ];
                    break;
                case ClassMetadataInfo::ONE_TO_MANY:
                    break;
                case ClassMetadataInfo::MANY_TO_MANY:
                    $singular = $mapping['fieldName'][mb_strlen($mapping['fieldName']) - 1] === 's' ?
                        mb_substr($mapping['fieldName'], 0, -1) : $mapping['fieldName'];

                    $methods = [
                        [
                            'get' . \ucfirst($mapping['fieldName']),
                            $mapping['fieldName'],
                            $input,
                            ArrayCollection::class
                        ],
                        [
                            'add' . \ucfirst($singular),
                            $mapping['fieldName'],
                            $input,
                            $this->entityName
                        ],
                        [
                            'remove' . \ucfirst($singular),
                            $mapping['fieldName'],
                            $input,
                            $this->entityName
                        ],
                        [
                            'clear' . \ucfirst($mapping['fieldName']),
                            $mapping['fieldName'],
                            $input,
                            $this->entityName
                        ]
                    ];
                    break;
            }

            return $methods;
        };

        $entityManager->close();
        $entityManager = null; // avoid memory leaks

        self::$kernel->shutdown();

        // These isn't associations, so return special values that marks test skipped
        if (empty($meta->getAssociationMappings())) {
            return [
                ['', '', null, null]
            ];
        }

        return \array_merge(...\array_values(\array_map($iterator, $meta->getAssociationMappings())));
    }

    /**
     * @return array
     */
    public function dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected(): array
    {
        self::bootKernel();

        // Get entity manager
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = function (array $mapping): array {
            return [
                [
                    'get' . \ucfirst($mapping['fieldName']),
                    $mapping['fieldName'],
                    $mapping
                ]
            ];
        };

        $filter = function (array $mapping): bool {
            return $mapping['type'] === ClassMetadataInfo::ONE_TO_MANY;
        };

        $entityManager->close();
        $entityManager = null; // avoid memory leaks

        self::$kernel->shutdown();

        $items = \array_filter($meta->getAssociationMappings(), $filter);

        if (empty($items)) {
            return [
                [false, false, []]
            ];
        }

        return \array_merge(...\array_values(\array_map($iterator, $items)));
    }
}
