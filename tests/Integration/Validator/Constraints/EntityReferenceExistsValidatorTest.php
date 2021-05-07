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
     * @testdox Test that `validate` method throws exception if constraint is not `EntityReferenceExists`
     */
    public function testThatValidateMethodThrowsUnexpectedTypeException(): void
    {
        [$loggerMock, , $constraintMock] = $this->getMocks();

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "App\Validator\Constraints\EntityReferenceExists", "SomeConstraint" given'
        );

        (new EntityReferenceExistsValidator($loggerMock))->validate('', $constraintMock);
    }

    /**
     * @dataProvider dataProviderTestThatValidateMethodThrowsUnexpectedValueException
     *
     * @param string|stdClass|array<array-key, string> $value
     *
     * @testdox      Test that `validate` method throws `$expectedMessage` with `$value` using entity class
     *               `$entityClass`
     */
    public function testThatValidateMethodThrowsUnexpectedValueException(
        string | stdClass | array $value,
        string $entityClass,
        string $expectedMessage
    ): void {
        [$loggerMock] = $this->getMocks();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedMessage);

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = $entityClass;

        (new EntityReferenceExistsValidator($loggerMock))->validate($value, $constraint);
    }

    /**
     * @testdox Test that `validate` method throws an exception if value is `stdClass`
     */
    public function testThatValidateMethodThrowsUnexpectedValueExceptionWhenValueIsNotEntityInterface(): void
    {
        [$loggerMock] = $this->getMocks();

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "App\Entity\Interfaces\EntityInterface", "stdClass" given'
        );

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = stdClass::class;

        (new EntityReferenceExistsValidator($loggerMock))->validate(new stdClass(), $constraint);
    }

    /**
     * @testdox Test that `validate` method doesn't call `Context` nor `Logger` methods with happy path
     */
    public function testThatContextAndLoggerMethodsAreNotCalledWithinHappyPath(): void
    {
        [$loggerMock, $executionContextMock, , $entityReferenceMock] = $this->getMocks();

        $executionContextMock
            ->expects(static::never())
            ->method(static::anything());

        $executionContextMock
            ->expects(static::never())
            ->method(static::anything());

        $entityReferenceMock
            ->expects(static::once())
            ->method('getCreatedAt')
            ->willReturn(null);

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = 'TestClass';

        // Run validator
        $validator = new EntityReferenceExistsValidator($loggerMock);
        $validator->initialize($executionContextMock);
        $validator->validate($entityReferenceMock, $constraint);
    }

    /**
     * @testdox Test that `validate` method calls expected `Context` and `Logger` service methods with unhappy path
     */
    public function testThatContextAndLoggerMethodsAreCalledIfEntityReferenceIsNotValidEntity(): void
    {
        $exception = new EntityNotFoundException('Entity not found');

        [$loggerMock, $executionContextMock, , $entityReferenceMock, $constraintViolationBuilder] = $this->getMocks();

        $constraintViolationBuilder
            ->expects(static::exactly(2))
            ->method('setParameter')
            ->willReturn($constraintViolationBuilder);

        $constraintViolationBuilder
            ->expects(static::once())
            ->method('setCode')
            ->with('64888b5e-bded-449b-82ed-0cc1f73df14d')
            ->willReturn($constraintViolationBuilder);

        $constraintViolationBuilder
            ->expects(static::once())
            ->method('addViolation');

        $executionContextMock
            ->expects(static::once())
            ->method('buildViolation')
            ->with('Invalid id value "{{ id }}" given for entity "{{ entity }}".')
            ->willReturn($constraintViolationBuilder);

        $loggerMock
            ->expects(static::once())
            ->method('error')
            ->with('Entity not found');

        $entityReferenceMock
            ->expects(static::once())
            ->method('getCreatedAt')
            ->willThrowException($exception);

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = EntityReference::class;

        // Run validator
        $validator = new EntityReferenceExistsValidator($loggerMock);
        $validator->initialize($executionContextMock);
        $validator->validate($entityReferenceMock, $constraint);
    }

    /**
     * @return Generator<array{0: string|stdClass|array, 1: string, 2: string}>
     */
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

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&LoggerInterface,
     *      1: \PHPUnit\Framework\MockObject\MockObject&ExecutionContext,
     *      2: \PHPUnit\Framework\MockObject\MockObject&Constraint,
     *      3: \PHPUnit\Framework\MockObject\MockObject&EntityReference,
     *      4: \PHPUnit\Framework\MockObject\MockObject&ConstraintViolationBuilderInterface,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(LoggerInterface::class)->getMock(),
            $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock(),
            $this->getMockForAbstractClass(Constraint::class, [], 'SomeConstraint'),
            $this->getMockForAbstractClass(EntityReference::class, [], 'TestClass'),
            $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock(),
        ];
    }
}
