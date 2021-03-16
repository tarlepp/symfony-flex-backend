<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Entity/EntityTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Entity;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\Role;
use App\Rest\UuidHelper;
use App\Utils\Tests\PhpUnitUtil;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Throwable;
use TypeError;
use UnexpectedValueException;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function get_class;
use function gettype;
use function in_array;
use function is_null;
use function is_object;
use function is_string;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * Class EntityTestCase
 *
 * @package App\Tests\Helpers
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class EntityTestCase extends KernelTestCase
{
    /**
     * @var class-string
     */
    protected string $entityName;
    protected ?EntityInterface $entity = null;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        static::bootKernel();

        $entity = $this->createEntity();

        $this->entity = $entity;
    }

    public function testThatGetUuidMethodReturnsExpected(): void
    {
        if (!method_exists($this->getEntity(), 'getUuid')) {
            static::markTestSkipped('Cannot test because `getUuid` method does not exist.');
        }

        static::assertSame($this->getEntity()->getUuid()->toString(), $this->getEntity()->getId());
    }

    /**
     * Generic method to test that getId method return expected UUID.
     */
    public function testThatGetIdReturnsCorrectUuid(): void
    {
        // Get entity UUID/ID
        $id = $this->getEntity()->getId();

        $factory = UuidHelper::getFactory();

        static::assertSame($id, $factory->fromString($id)->toString());
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param array<string, mixed> $meta
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
            method_exists($this->getEntity(), $getter),
            sprintf(
                "Entity '%s' does not have expected getter '%s()' method for '%s' property.",
                $this->entityName,
                $getter,
                $field,
            ),
        );

        if (array_key_exists('columnName', $meta)) {
            $message = $readOnly
                ? "Entity '%s' has not expected setter '%s()' method for '%s' property."
                : "Entity '%s' does not have expected setter '%s()' method for '%s' property.";

            static::assertSame(
                !$readOnly,
                method_exists($this->getEntity(), $setter),
                sprintf($message, $this->entityName, $setter, $field),
            );
        }
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param array<string, mixed> $meta
     *
     * @throws Throwable
     *
     * @testdox Test that `setter` method for `$field` field only accepts `$type` parameter.
     */
    public function testThatSetterOnlyAcceptSpecifiedType(
        string $field,
        string $type,
        array $meta,
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
            is_object($value) ? gettype($value) : '(' . gettype($value) . ')' . $value,
        );

        static::fail($message);
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param array<string, string> $meta
     *
     * @throws Throwable
     *
     * @testdox Test that `setter` method for `$field` field is fluent.
     */
    public function testThatSetterReturnsInstanceOfEntity(
        string $field,
        string $type,
        array $meta,
    ): void {
        $setter = 'set' . ucfirst($field);

        if (!array_key_exists('columnName', $meta)) {
            static::markTestSkipped('No need to test this setter...');
        }

        /**
         * @var callable $callable
         */
        $callable = [$this->entity, $setter];

        static::assertInstanceOf(
            get_class($this->getEntity()),
            $callable(PhpUnitUtil::getValidValueForType($type, $meta)),
            sprintf(
                "Entity '%s' setter '%s()' method for '%s' property did not return expected value.",
                $this->entityName,
                $setter,
                $field,
            ),
        );
    }

    /**
     * @dataProvider dataProviderTestThatSetterAndGettersWorks
     *
     * @param array<string, string> $meta
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

        /**
         * @var callable $callable
         */
        $callable = [$this->entity, $getter];

        if (array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta)) {
            $value = PhpUnitUtil::getValidValueForType($type, $meta);

            $this->entity->{$setter}($value);

            static::assertSame(
                $value,
                $callable(),
                sprintf(
                    'Getter method of %s:%s did not return expected value type (%s) and it returned (%s)',
                    $this->entityName,
                    $getter,
                    gettype($value),
                    gettype($callable()),
                ),
            );
        } else {
            $type = ArrayCollection::class;

            static::assertInstanceOf($type, $callable());
        }

        try {
            $method = 'assertIs' . ucfirst($type);

            static::$method($this->entity->{$getter}());
        } catch (Throwable $error) {
            /** @var class-string $type */
            static::assertInstanceOf($type, $callable(), $error->getMessage());
        }
    }

    /**
     * @dataProvider dataProviderTestThatAssociationMethodsExists
     *
     * @psalm-param class-string | null $output
     *
     * @testdox Test that association method `$method` exist for `$field` and it returns `$output` when using `$input`.
     */
    public function testThatAssociationMethodsExistsAndThoseReturnsCorrectValue(
        ?string $method,
        ?string $field,
        mixed $input,
        ?string $output,
    ): void {
        if (is_null($method)) {
            static::markTestSkipped("Entity doesn't have associations, so cannot test those...");
        }

        static::assertNotNull($field);

        static::assertTrue(
            method_exists($this->getEntity(), $method),
            sprintf(
                "Entity '%s' does not have expected association method '%s()' for property '%s'.",
                $this->entityName,
                $method,
                $field,
            ),
        );

        if (is_string($output)) {
            static::assertInstanceOf($output, $this->entity->$method($input));
        }
    }

    /**
     * @dataProvider dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected
     *
     * @param array<mixed> $mappings
     *
     * @testdox Test that `many-to-many` assoc methods `$getter, $adder, $removal, $clear` works for `$field + $entity`.
     */
    public function testThatManyToManyAssociationMethodsWorksAsExpected(
        ?string $getter,
        ?string $adder,
        ?string $removal,
        ?string $clear,
        ?string $field,
        ?EntityInterface $entity,
        array $mappings,
    ): void {
        if (is_null($getter)) {
            static::markTestSkipped('Entity does not contain many-to-many relationships.');
        }

        static::assertNotNull($adder);
        static::assertNotNull($removal);
        static::assertNotNull($clear);
        static::assertNotNull($field);
        static::assertNotNull($entity);

        static::assertInstanceOf(
            get_class($this->getEntity()),
            $this->entity->$adder($entity),
            sprintf(
                "Added method '%s()' for property '%s' did not return instance of the entity itself",
                $adder,
                $field,
            ),
        );

        /** @var ArrayCollection $collection */
        $collection = $this->entity->$getter();

        static::assertTrue($collection->contains($entity));

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
            get_class($this->getEntity()),
            $this->entity->$removal($entity),
            sprintf(
                "Removal method '%s()' for property '%s' did not return instance of the entity itself",
                $adder,
                $field,
            ),
        );

        /** @var ArrayCollection $collection */
        $collection = $this->entity->$getter();

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
            get_class($this->getEntity()),
            $this->entity->$clear(),
            sprintf(
                "Clear method '%s()' for property '%s' did not return instance of the entity itself",
                $adder,
                $field,
            ),
        );

        /** @var ArrayCollection $collection */
        $collection = $this->entity->$getter();

        static::assertTrue($collection->isEmpty());
    }

    /**
     * @dataProvider dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected
     *
     * @testdox Test that `ManyToOne` assoc methods `$getter` and `$setter` works for `$field + $targetEntity`.
     */
    public function testThatManyToOneAssociationMethodsWorksAsExpected(
        ?string $setter,
        ?string $getter,
        ?EntityInterface $targetEntity,
        ?string $field
    ): void {
        if (is_null($setter)) {
            static::markTestSkipped('Entity does not contain many-to-one relationships.');
        }

        static::assertNotNull($getter);
        static::assertNotNull($field);

        static::assertInstanceOf(
            get_class($this->getEntity()),
            $this->entity->$setter($targetEntity),
            sprintf(
                "Setter method '%s()' for property '%s' did not return instance of the entity itself",
                $setter,
                $field,
            ),
        );

        assert($targetEntity !== null);

        static::assertInstanceOf(
            get_class($targetEntity),
            $this->entity->$getter(),
            sprintf(
                "Getter method '%s()' for property '%s' did not return expected object '%s'.",
                $getter,
                $field,
                get_class($targetEntity),
            ),
        );
    }

    /**
     * @dataProvider dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected
     *
     * @testdox Test that `$methodGetter` method works as expected for `$field` field.
     */
    public function testThatOneToManyAssociationMethodsWorksAsExpected(?string $methodGetter, ?string $field): void
    {
        if (is_null($methodGetter)) {
            static::markTestSkipped('Entity does not contain one-to-many relationships.');
        }

        static::assertNotNull($field);

        static::assertInstanceOf(
            ArrayCollection::class,
            $this->entity->$methodGetter(),
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
     *
     * @return array<mixed>
     */
    public function dataProviderTestThatSetterAndGettersWorks(): array
    {
        static::bootKernel();

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
         * @return array
         */
        $iterator = static fn (string $field): array => [
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
                    static fn (string $field): bool => !in_array($field, $fieldsToOmit, true)
                )
            ),
            $assocFields
        );
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected(): array
    {
        static::bootKernel();

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
            static fn ($mapping): bool => $mapping['type'] === ClassMetadataInfo::MANY_TO_MANY
        );

        if (empty($items)) {
            $output = [
                [null, null, null, null, null, null, []],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected(): array
    {
        static::bootKernel();

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
                    $meta->isReadOnly ? null : 'set' . ucfirst($mapping['fieldName']),
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
            static fn (array $mapping): bool => $mapping['type'] === ClassMetadataInfo::MANY_TO_ONE
        );

        if (empty($items)) {
            $output = [
                [null, null, null, null, []],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderTestThatAssociationMethodsExists(): array
    {
        static::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = function (array $mapping) use ($meta): array {
            /** @var class-string $target */
            $target = $mapping['targetEntity'];

            $input = $this->createMock($target);

            $methods = [
                ['get' . ucfirst($mapping['fieldName']), $mapping['fieldName'], false, null],
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
                [null, null, null, null],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $meta->getAssociationMappings())));
        }

        return $output;
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected(): array
    {
        static::bootKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::$kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata($this->entityName);

        $iterator = static fn (array $mapping): array => [
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
            static fn (array $mapping): bool => $mapping['type'] === ClassMetadataInfo::ONE_TO_MANY,
        );

        if (empty($items)) {
            $output = [
                [null, null, []],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    protected function getEntity(): EntityInterface
    {
        return $this->entity instanceof EntityInterface
            ? $this->entity
            : throw new UnexpectedValueException('Entity not set');
    }

    protected function createEntity(): EntityInterface
    {
        /**
         * @var EntityInterface $entity
         */
        $entity = new $this->entityName();

        return $entity;
    }
}
