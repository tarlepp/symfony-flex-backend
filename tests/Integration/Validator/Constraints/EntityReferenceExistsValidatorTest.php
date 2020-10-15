<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/EntityReferenceExistsValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\Interfaces\EntityInterface;
use App\Tests\Integration\Validator\Constraints\src\EntityReference;
use App\Validator\Constraints\EntityReferenceExists;
use App\Validator\Constraints\EntityReferenceExistsValidator;
use Doctrine\ORM\EntityNotFoundException;
use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Class EntityReferenceExistsValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EntityReferenceExistsValidatorTest extends KernelTestCase
{
    /**
     * @var MockObject|LoggerInterface
     */
    private $logger;

    /**
     * @var MockObject|ExecutionContext
     */
    private $context;

    /**
     * @testdox Test that `validate` method throws exception if constraint is not `EntityReferenceExists`
     */
    public function testThatValidateMethodThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "App\Validator\Constraints\EntityReferenceExists", "SomeConstraint" given'
        );

        $constraint = $this->getMockForAbstractClass(Constraint::class, [], 'SomeConstraint');

        (new EntityReferenceExistsValidator($this->logger))
            ->validate('', $constraint);
    }

    /**
     * @dataProvider dataProviderTestThatValidateMethodThrowsUnexpectedValueException
     *
     * @param mixed $value
     * @param mixed $entityClass
     *
     * @testdox Test that `validate` method throws `$expectedMessage` with `$value` using entity class `$entityClass`
     */
    public function testThatValidateMethodThrowsUnexpectedValueException(
        $value,
        $entityClass,
        string $expectedMessage
    ): void {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = $entityClass;

        (new EntityReferenceExistsValidator($this->logger))
            ->validate($value, $constraint);
    }

    /**
     * @testdox Test that `validate` method throws an exception if value is `stdClass`
     */
    public function testThatValidateMethodThrowsUnexpectedValueExceptionWhenValueIsNotEntityInterface(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "App\Entity\Interfaces\EntityInterface", "stdClass" given'
        );

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = stdClass::class;

        (new EntityReferenceExistsValidator($this->logger))
            ->validate(new stdClass(), $constraint);
    }

    /**
     * @testdox Test that `validate` method doesn't call `Context` nor `Logger` methods with happy path
     */
    public function testThatContextAndLoggerMethodsAreNotCalledWithinHappyPath(): void
    {
        /**
         * @var MockObject|EntityInterface $value
         */
        $value = $this->getMockForAbstractClass(EntityReference::class, [], 'TestClass');

        $this->context
            ->expects(static::never())
            ->method(static::anything());

        $this->logger
            ->expects(static::never())
            ->method(static::anything());

        $value
            ->expects(static::once())
            ->method('getCreatedAt')
            ->willReturn(null);

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = 'TestClass';

        // Run validator
        $validator = new EntityReferenceExistsValidator($this->logger);
        $validator->initialize($this->context);
        $validator->validate($value, $constraint);
    }

    /**
     * @testdox Test that `validate` method calls expected `Context` and `Logger` service methods with unhappy path
     */
    public function testThatContextAndLoggerMethodsAreCalledIfEntityReferenceIsNotValidEntity(): void
    {
        /**
         * @var MockObject|ConstraintViolationBuilderInterface $violation
         * @var MockObject|EntityInterface $value
         */
        $violation = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $value = $this->getMockForAbstractClass(EntityReference::class);

        $exception = new EntityNotFoundException('Entity not found');

        $violation
            ->expects(static::exactly(2))
            ->method('setParameter')
            ->willReturn($violation);

        $violation
            ->expects(static::once())
            ->method('setCode')
            ->with('64888b5e-bded-449b-82ed-0cc1f73df14d')
            ->willReturn($violation);

        $violation
            ->expects(static::once())
            ->method('addViolation');

        $this->context
            ->expects(static::once())
            ->method('buildViolation')
            ->with('Invalid id value "{{ id }}" given for entity "{{ entity }}".')
            ->willReturn($violation);

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with('Entity not found');

        $value
            ->expects(static::once())
            ->method('getCreatedAt')
            ->willThrowException($exception);

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = EntityReference::class;

        // Run validator
        $validator = new EntityReferenceExistsValidator($this->logger);
        $validator->initialize($this->context);
        $validator->validate($value, $constraint);
    }

    public function dataProviderTestThatValidateMethodThrowsUnexpectedValueException(): Generator
    {
        yield ['', stdClass::class, 'Expected argument of type "stdClass", "string" given'];

        yield [
            new stdClass(),
            EntityInterface::class,
            'Expected argument of type "App\Entity\Interfaces\EntityInterface", "stdClass" given',
        ];

        yield [
            [''],
            EntityInterface::class,
            'Expected argument of type "App\Entity\Interfaces\EntityInterface", "string" given',
        ];

        yield [
            [new stdClass()],
            EntityInterface::class,
            'Expected argument of type "App\Entity\Interfaces\EntityInterface", "stdClass" given',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->context = $this->getMockBuilder(ExecutionContext::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
