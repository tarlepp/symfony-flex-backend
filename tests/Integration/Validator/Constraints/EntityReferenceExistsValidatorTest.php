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
     * @param string $expectedMessage
     */
    public function testThatValidateMethodThrowsUnexpectedValueException(
        $value,
        string $expectedMessage
    ): void {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage($expectedMessage);

        (new EntityReferenceExistsValidator())->validate($value, new EntityReferenceExists());
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
        $value = $this->getMockForAbstractClass(EntityReference::class);

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

        // Run validator
        $validator = new EntityReferenceExistsValidator();
        $validator->initialize($context);
        $validator->setLogger($logger);
        $validator->validate($value, new EntityReferenceExists());
    }

    /**
     * @return Generator
     */
    public function dataProviderTestThatValidateMethodThrowsUnexpectedValueException(): Generator
    {
        yield ['', 'Expected argument of type "Doctrine\ORM\Proxy\Proxy", "string" given'];

        yield [new stdClass(), 'Expected argument of type "Doctrine\ORM\Proxy\Proxy", "stdClass" given'];

        yield [[''], 'Expected argument of type "Doctrine\ORM\Proxy\Proxy", "string" given'];

        yield [[new stdClass()], 'Expected argument of type "Doctrine\ORM\Proxy\Proxy", "stdClass" given'];

        yield [
            $this->getMockForAbstractClass(Proxy::class, [], 'ProxyClass'),
            'Expected argument of type "App\Entity\EntityInterface", "ProxyClass" given',
        ];

        yield [
            [$this->getMockForAbstractClass(Proxy::class, [], 'ProxyClass')],
            'Expected argument of type "App\Entity\EntityInterface", "ProxyClass" given',
        ];
    }
}
