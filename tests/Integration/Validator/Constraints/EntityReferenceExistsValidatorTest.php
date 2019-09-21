<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/EntityReferenceExistsValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\EntityInterface;
use App\Tests\Integration\Validator\Constraints\src\EntityReference;
use App\Validator\Constraints\EntityReferenceExists;
use App\Validator\Constraints\EntityReferenceExistsValidator;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\Proxy\Proxy;
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
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EntityReferenceExistsValidatorTest extends KernelTestCase
{
    public function testThatValidateMethodThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "App\Validator\Constraints\EntityReferenceExists", "SomeConstraint" given'
        );

        $constraint = $this->getMockForAbstractClass(Constraint::class, [], 'SomeConstraint');

        (new EntityReferenceExistsValidator())->validate('', $constraint);
    }

    /**
     * @dataProvider dataProviderTestThatValidateMethodThrowsUnexpectedValueException
     *
     * @param mixed  $value
     * @param mixed  $entityClass
     * @param string $expectedMessage
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

        (new EntityReferenceExistsValidator())->validate($value, $constraint);
    }

    public function testThatContextAndLoggerMethodsAreNotCalledWithinHappyPath(): void
    {
        /**
         * @var MockObject|ExecutionContext $context
         * @var MockObject|LoggerInterface  $logger
         * @var MockObject|EntityInterface  $value
         */
        $context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $value = $this->getMockForAbstractClass(EntityReference::class, [], 'TestClass');

        $context
            ->expects(static::never())
            ->method(static::anything());

        $logger
            ->expects(static::never())
            ->method(static::anything());

        $value
            ->expects(static::once())
            ->method('getCreatedAt')
            ->willReturn(null);

        $constraint = new EntityReferenceExists();
        $constraint->entityClass = 'TestClass';

        // Run validator
        $validator = new EntityReferenceExistsValidator();
        $validator->initialize($context);
        $validator->setLogger($logger);
        $validator->validate($value, $constraint);
    }

    public function testThatContextAndLoggerMethodsAreCalledIfEntityReferenceIsNotValidEntity(): void
    {
        /**
         * @var MockObject|ConstraintViolationBuilderInterface $violation
         * @var MockObject|ExecutionContext                    $context
         * @var MockObject|LoggerInterface                     $logger
         * @var MockObject|EntityInterface                     $value
         */
        $violation = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
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

        $context
            ->expects(static::once())
            ->method('buildViolation')
            ->with('Invalid id value "{{ id }}" given for entity "{{ entity }}".')
            ->willReturn($violation);

        $logger
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
        $validator = new EntityReferenceExistsValidator();
        $validator->initialize($context);
        $validator->setLogger($logger);
        $validator->validate($value, $constraint);
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatValidateMethodThrowsUnexpectedValueException(): Generator
    {
        yield ['', stdClass::class, 'Expected argument of type "stdClass", "string" given'];

        yield [
            new stdClass(),
            EntityInterface::class,
            'Expected argument of type "App\Entity\EntityInterface", "stdClass" given'
        ];

        yield [
            [''],
            EntityInterface::class,
            'Expected argument of type "App\Entity\EntityInterface", "string" given'
        ];

        yield [
            [new stdClass()],
            EntityInterface::class,
            'Expected argument of type "App\Entity\EntityInterface", "stdClass" given'
        ];

        yield [
            $this->getMockForAbstractClass(Proxy::class, [], 'ProxyClass'),
            Proxy::class,
            'Expected argument of type "App\Entity\EntityInterface", "ProxyClass" given',
        ];

        yield [
            [$this->getMockForAbstractClass(Proxy::class, [], 'ProxyClass')],
            Proxy::class,
            'Expected argument of type "App\Entity\EntityInterface", "ProxyClass" given',
        ];
    }
}
