<?php
declare(strict_types = 1);
/**
 * /tests/Integration/TestCase/EntityTestCase.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\TestCase;

use App\Entity\Interfaces\EntityInterface;
use App\Entity\LogLogin;
use App\Entity\LogLoginFailure;
use App\Entity\LogRequest;
use App\Entity\Role;
use App\Entity\User;
use App\Enum\LogLogin as LogLoginEnum;
use App\Rest\UuidHelper;
use App\Tests\Utils\PhpUnitUtil;
use DeviceDetector\DeviceDetector;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;
use TypeError;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function class_exists;
use function gettype;
use function in_array;
use function is_object;
use function is_string;
use function mb_strlen;
use function mb_substr;
use function method_exists;
use function sprintf;
use function ucfirst;

/**
 * @package App\Tests\Integration\TestCase
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class EntityTestCase extends KernelTestCase
{
    /**
     * @var class-string
     */
    protected static string $entityName;

    public static function getKernel(): KernelInterface
    {
        self::bootKernel();

        if (self::$kernel === null) {
            throw new RuntimeException('Kernel is not booting.');
        }

        return self::$kernel;
    }

    #[TestDox(
        'Test that `getUuid` method returns UUID object which contains same UUID string value as `getId` method'
    )]
    public function testThatGetUuidMethodReturnsExpected(): void
    {
        $entity = $this->getEntity();

        if (!method_exists($entity, 'getUuid')) {
            self::markTestSkipped('Cannot test because `getUuid` method does not exist.');
        }

        self::assertSame($entity->getUuid()->toString(), $entity->getId());
    }

    #[TestDox('Test that `getId` method returns expected UUID string')]
    public function testThatGetIdReturnsCorrectUuid(): void
    {
        $entity = $this->getEntity();
        $id = $entity->getId();

        $factory = UuidHelper::getFactory();

        self::assertSame($id, $factory->fromString($id)->toString());
    }

    /**
     * @param array<string, mixed> $meta
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorks')]
    #[TestDox('Test that `getter` and `setter` methods exists for `$type $property` property')]
    public function testThatGetterAndSetterExists(string $property, string $type, array $meta, bool $readOnly): void
    {
        $entity = $this->getEntity();
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($property);
        }

        self::assertTrue(
            method_exists($entity, $getter),
            sprintf(
                "Entity '%s' does not have expected getter '%s()' method for '%s' property.",
                static::$entityName,
                $getter,
                $property,
            ),
        );

        if (array_key_exists('columnName', $meta)) {
            $message = $readOnly
                ? "Entity '%s' has not expected setter '%s()' method for '%s' property."
                : "Entity '%s' does not have expected setter '%s()' method for '%s' property.";

            self::assertSame(
                !$readOnly,
                method_exists($entity, $setter),
                sprintf($message, static::$entityName, $setter, $property),
            );
        }
    }

    /**
     * @param array<string, mixed> $meta
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('Test that `setter` method for `$property` property only accepts `$type` parameter')]
    public function testThatSetterOnlyAcceptSpecifiedType(
        string $property,
        string $type,
        array $meta,
    ): void {
        if (!array_key_exists('columnName', $meta) && !array_key_exists('joinColumns', $meta)) {
            self::markTestSkipped('No need to test this setter...');
        }

        $this->expectException(TypeError::class);

        $entity = $this->getEntity();
        $setter = 'set' . ucfirst($property);
        $value = PhpUnitUtil::getInvalidValueForType($type);

        $entity->{$setter}($value);

        $message = sprintf(
            "Setter '%s' didn't fail with invalid value type '%s', maybe missing variable type?",
            $setter,
            is_object($value) ? gettype($value) : '(' . gettype($value) . ')' . $value,
        );

        self::fail($message);
    }

    /**
     * @param array<string, string> $meta
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('Test that `setter` method for `$type $property` property is fluent')]
    public function testThatSetterReturnsInstanceOfEntity(
        string $property,
        string $type,
        array $meta,
    ): void {
        if (!array_key_exists('columnName', $meta)) {
            self::markTestSkipped('No need to test this setter...');
        }

        $entity = $this->getEntity();
        $setter = 'set' . ucfirst($property);

        /** @var callable $callable */
        $callable = [$entity, $setter];

        self::assertInstanceOf(
            $entity::class,
            $callable(PhpUnitUtil::getValidValueForType($type, $meta)),
            sprintf(
                "Entity '%s' setter '%s()' method for '%s' property did not return expected value.",
                static::$entityName,
                $setter,
                $property,
            ),
        );
    }

    /**
     * @param array<string, string> $meta
     *
     * @throws Throwable
     */
    #[DataProvider('dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag')]
    #[TestDox('Test that `getter` method for `$property` property returns value of expected type `$type`')]
    public function testThatGetterReturnsExpectedValue(string $property, string $type, array $meta): void
    {
        $entity = $this->getEntity();
        $getter = 'get' . ucfirst($property);
        $setter = 'set' . ucfirst($property);

        if (in_array($type, [PhpUnitUtil::TYPE_BOOL, PhpUnitUtil::TYPE_BOOLEAN], true)) {
            $getter = 'is' . ucfirst($property);
        }

        /** @var callable $callable */
        $callable = [$entity, $getter];

        if (array_key_exists('columnName', $meta) || array_key_exists('joinColumns', $meta)) {
            $value = PhpUnitUtil::getValidValueForType($type, $meta);

            $entity->{$setter}($value);

            self::assertSame(
                $value,
                $callable(),
                sprintf(
                    'Getter method of %s:%s did not return expected value type (%s) and it returned (%s)',
                    static::$entityName,
                    $getter,
                    gettype($value),
                    gettype($callable()),
                ),
            );
        } else {
            $type = ArrayCollection::class;

            self::assertInstanceOf($type, $callable());
        }

        try {
            $method = 'assertIs' . ucfirst($type);

            self::$method($entity->{$getter}());
        } catch (Throwable $error) {
            /** @var class-string $type */
            self::assertInstanceOf($type, $callable(), $error->getMessage());
        }
    }

    /**
     * @psalm-param class-string | null $o
     */
    #[DataProvider('dataProviderTestThatAssociationMethodsExists')]
    #[TestDox(
        'Test that association method `$m` exists for `$p` property, and it returns `$o` when using `$i` as input'
    )]
    public function testThatAssociationMethodsExistsAndThoseReturnsCorrectValue(
        ?string $m,
        ?string $p,
        mixed $i,
        ?string $o,
    ): void {
        if ($m === null) {
            self::markTestSkipped("Entity doesn't have associations, so cannot test those...");
        }

        $entity = $this->getEntity();

        self::assertNotNull($p);

        self::assertTrue(
            method_exists($entity, $m),
            sprintf(
                "Entity '%s' does not have expected association method '%s()' for property '%s'.",
                static::$entityName,
                $m,
                $p,
            ),
        );

        if (is_string($o)) {
            self::assertInstanceOf($o, $entity->{$m}($i));
        }
    }

    /**
     * @param array<mixed> $m
     */
    #[DataProvider('dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected')]
    #[TestDox('Test that `many-to-many` association methods `$g, $a, $r, $c` works as expected for `$e + $p` combo')]
    public function testThatManyToManyAssociationMethodsWorksAsExpected(
        ?string $g,
        ?string $a,
        ?string $r,
        ?string $c,
        ?string $p,
        ?EntityInterface $e,
        array $m,
    ): void {
        if ($g === null) {
            self::markTestSkipped('Entity does not contain many-to-many relationships.');
        }

        $entity = $this->getEntity();

        self::assertNotNull($a);
        self::assertNotNull($r);
        self::assertNotNull($c);
        self::assertNotNull($p);
        self::assertNotNull($e);

        self::assertInstanceOf(
            $entity::class,
            $entity->{$a}($e),
            sprintf(
                "Added method '%s()' for property '%s' did not return instance of the entity itself",
                $a,
                $p,
            ),
        );

        /** @var ArrayCollection<int, EntityInterface> $collection */
        $collection = $entity->{$g}();

        self::assertTrue($collection->contains($e));

        if (isset($m['mappedBy'])) {
            /** @var ArrayCollection<int, EntityInterface> $collection */
            $collection = $e->{'get' . ucfirst((string)$m['mappedBy'])}();

            self::assertTrue($collection->contains($entity));
        } elseif (isset($m['inversedBy'])) {
            /** @var ArrayCollection<int, EntityInterface> $collection */
            $collection = $e->{'get' . ucfirst((string)$m['inversedBy'])}();

            self::assertTrue($collection->contains($entity));
        }

        self::assertInstanceOf(
            $entity::class,
            $entity->{$r}($e),
            sprintf(
                "Removal method '%s()' for property '%s' did not return instance of the entity itself",
                $a,
                $p,
            ),
        );

        /** @var ArrayCollection<int, EntityInterface> $collection */
        $collection = $entity->{$g}();

        self::assertTrue($collection->isEmpty());

        if (isset($m['mappedBy'])) {
            /** @var ArrayCollection<int, EntityInterface> $collection */
            $collection = $e->{'get' . ucfirst((string)$m['mappedBy'])}();

            self::assertTrue($collection->isEmpty());
        } elseif (isset($m['inversedBy'])) {
            /** @var ArrayCollection<int, EntityInterface> $collection */
            $collection = $e->{'get' . ucfirst((string)$m['inversedBy'])}();

            self::assertTrue($collection->isEmpty());
        }

        // Test for 'clear' method
        $entity->{$a}($e);

        self::assertInstanceOf(
            $entity::class,
            $entity->{$c}(),
            sprintf(
                "Clear method '%s()' for property '%s' did not return instance of the entity itself",
                $a,
                $p,
            ),
        );

        /** @var ArrayCollection<int, EntityInterface> $collection */
        $collection = $entity->{$g}();

        self::assertTrue($collection->isEmpty());
    }

    #[DataProvider('dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected')]
    #[TestDox('Test that `many-to-many` association methods `$g` and `$s` works for `$p + $te` combo')]
    public function testThatManyToOneAssociationMethodsWorksAsExpected(
        ?string $s,
        ?string $g,
        ?EntityInterface $te,
        ?string $p
    ): void {
        if ($s === null) {
            self::markTestSkipped('Entity does not contain many-to-one relationships.');
        }

        $entity = $this->getEntity();

        self::assertNotNull($g);
        self::assertNotNull($p);

        self::assertInstanceOf(
            $entity::class,
            $entity->{$s}($te),
            sprintf(
                "Setter method '%s()' for property '%s' did not return instance of the entity itself",
                $s,
                $p,
            ),
        );

        self::assertNotNull($te);

        self::assertInstanceOf(
            $te::class,
            $entity->{$g}(),
            sprintf(
                "Getter method '%s()' for property '%s' did not return expected object '%s'.",
                $g,
                $p,
                $te::class,
            ),
        );
    }

    #[DataProvider('dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected')]
    #[TestDox('Test that `one-to-many` association `$getter` method works as expected for `$property` property')]
    public function testThatOneToManyAssociationMethodsWorksAsExpected(?string $getter, ?string $property): void
    {
        if ($getter === null) {
            self::markTestSkipped('Entity does not contain one-to-many relationships.');
        }

        $entity = $this->getEntity();

        self::assertNotNull($property);

        self::assertInstanceOf(
            ArrayCollection::class,
            $entity->{$getter}(),
            sprintf(
                "Getter method '%s()' for property '%s' did not return expected 'ArrayCollection' object.",
                $getter,
                $property
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
    public static function dataProviderTestThatSetterAndGettersWorks(): array
    {
        $kernel = self::getKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata(static::$entityName);

        /**
         * Lambda function to generate actual test case arrays for tests. Output value is an array which contains
         * following data:
         *  1) Name
         *  2) Type
         *  3) Mapping data
         *  4) Read-only flag
         *
         * @return array
         */
        $iterator = static fn (string $field): array => [
            $field,
            PhpUnitUtil::getType($meta->getTypeOfField($field)),
            $meta->getFieldMapping($field),
            $meta->isReadOnly,
        ];

        $fieldsToOmit = [...$meta->getIdentifierFieldNames(), ...['password']];

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

        return [...array_map(
            $iterator,
            array_filter(
                $meta->getFieldNames(),
                static fn (string $field): bool => !in_array($field, $fieldsToOmit, true)
            )
        ), ...$assocFields];
    }

    public static function dataProviderTestThatSetterAndGettersWorksWithoutReadOnlyFlag(): Generator
    {
        foreach (self::dataProviderTestThatSetterAndGettersWorks() as $data) {
            self::assertIsArray($data);
            self::assertCount(4, $data);

            // Remove 'Read-only flag' from data
            unset($data[3]);

            yield $data;
        }
    }

    /**
     * @return array<mixed>
     */
    public static function dataProviderTestThatManyToManyAssociationMethodsWorksAsExpected(): array
    {
        $kernel = self::getKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata(static::$entityName);

        $iterator = static function (array $mapping): array {
            $class = $mapping['targetEntity'];

            self::assertIsString($class);
            self::assertTrue(class_exists($class));

            $targetEntity = new $class();

            $singular = $mapping['fieldName'][mb_strlen((string)$mapping['fieldName']) - 1] === 's'
                ? mb_substr((string)$mapping['fieldName'], 0, -1)
                : $mapping['fieldName'];

            self::assertIsString($singular);

            return [
                [
                    'get' . ucfirst((string)$mapping['fieldName']),
                    'add' . ucfirst($singular),
                    'remove' . ucfirst($singular),
                    'clear' . ucfirst((string)$mapping['fieldName']),
                    $mapping['fieldName'],
                    $targetEntity,
                    $mapping,
                ],
            ];
        };

        $entityManager->close();

        $kernel->shutdown();

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
    public static function dataProviderTestThatManyToOneAssociationMethodsWorksAsExpected(): array
    {
        $kernel = self::getKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata(static::$entityName);

        $iterator = static function (array $mapping) use ($meta): array {
            $params = [null];

            if ($mapping['targetEntity'] === Role::class) {
                $params = ['Some Role'];
            }

            $targetEntity = new $mapping['targetEntity'](...$params);

            return [
                [
                    $meta->isReadOnly ? null : 'set' . ucfirst((string)$mapping['fieldName']),
                    'get' . ucfirst((string)$mapping['fieldName']),
                    $targetEntity,
                    $mapping['fieldName'],
                ],
            ];
        };

        $entityManager->close();

        $kernel->shutdown();

        $items = array_filter(
            $meta->getAssociationMappings(),
            static fn (array $mapping): bool => $mapping['type'] === ClassMetadataInfo::MANY_TO_ONE
        );

        if (empty($items)) {
            $output = [
                [null, null, null, null],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    /**
     * @return array<mixed>
     */
    public static function dataProviderTestThatAssociationMethodsExists(): array
    {
        $kernel = self::getKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata(static::$entityName);

        $iterator = static function (array $mapping) use ($meta): array {
            $target = $mapping['targetEntity'];

            self::assertIsString($target);
            self::assertTrue(class_exists($target));

            $arguments = match ($target) {
                LogLogin::class => [LogLoginEnum::SUCCESS, new Request(), new DeviceDetector()],
                LogLoginFailure::class => [new User()],
                LogRequest::class => [[]],
                Role::class => ['some role'],
                default => [],
            };

            $input = new $target(...$arguments);

            $methods = [
                ['get' . ucfirst((string)$mapping['fieldName']), $mapping['fieldName'], false, null],
            ];

            switch ($mapping['type']) {
                case ClassMetadataInfo::ONE_TO_MANY:
                case ClassMetadataInfo::ONE_TO_ONE:
                    break;
                case ClassMetadataInfo::MANY_TO_ONE:
                    if ($meta->isReadOnly === false) {
                        $methods[] = [
                            'set' . ucfirst((string)$mapping['fieldName']),
                            $mapping['fieldName'],
                            $input,
                            static::$entityName,
                        ];
                    }
                    break;
                case ClassMetadataInfo::MANY_TO_MANY:
                    self::assertArrayHasKey('fieldName', $mapping);

                    $singular = $mapping['fieldName'][mb_strlen((string)$mapping['fieldName']) - 1] === 's'
                        ? mb_substr((string)$mapping['fieldName'], 0, -1)
                        : $mapping['fieldName'];

                    self::assertIsString($singular);

                    $methods = [
                        [
                            'get' . ucfirst((string)$mapping['fieldName']),
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
                                static::$entityName,
                            ],
                            [
                                'remove' . ucfirst($singular),
                                $mapping['fieldName'],
                                $input,
                                static::$entityName,
                            ],
                            [
                                'clear' . ucfirst((string)$mapping['fieldName']),
                                $mapping['fieldName'],
                                $input,
                                static::$entityName,
                            ],
                        ];

                        $methods = [...$methods, ...$setters];
                    }
                    break;
            }

            return $methods;
        };

        $entityManager->close();

        $kernel->shutdown();

        // There isn't associations, so return special values that marks test skipped
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
    public static function dataProviderTestThatOneToManyAssociationMethodsWorksAsExpected(): array
    {
        $kernel = self::getKernel();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.default_entity_manager');

        // Get entity class meta data
        $meta = $entityManager->getClassMetadata(static::$entityName);

        $iterator = static fn (array $mapping): array => [
            [
                'get' . ucfirst((string)$mapping['fieldName']),
                $mapping['fieldName'],
            ],
        ];

        $entityManager->close();

        $kernel->shutdown();

        $items = array_filter(
            $meta->getAssociationMappings(),
            static fn (array $mapping): bool => $mapping['type'] === ClassMetadataInfo::ONE_TO_MANY,
        );

        if (empty($items)) {
            $output = [
                [null, null],
            ];
        } else {
            $output = array_merge(...array_values(array_map($iterator, $items)));
        }

        return $output;
    }

    protected function getEntity(): EntityInterface
    {
        return $this->createEntity();
    }

    protected function createEntity(): EntityInterface
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        self::assertTrue(class_exists(static::$entityName));

        $entity = new static::$entityName();

        self::assertInstanceOf(EntityInterface::class, $entity);

        return $entity;
    }
}
