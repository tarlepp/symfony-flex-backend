<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/LocaleValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Service\Localization;
use App\Validator\Constraints\Locale;
use App\Validator\Constraints\LocaleValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Class LocaleValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocaleValidatorTest extends KernelTestCase
{
    /**
     * @testdox Test that `LocaleValidator::validate` method calls expected service methods
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        [$constraintViolationBuilderMock, $executionContextMock, $localizationMock] = $this->getMocks();

        $localizationMock
            ->expects(static::once())
            ->method('getLocales')
            ->willReturn(['bar']);

        $executionContextMock
            ->expects(static::once())
            ->method('buildViolation')
            ->with(Locale::MESSAGE)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('setParameter')
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('setCode')
            ->with(Locale::INVALID_LOCALE)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new LocaleValidator($localizationMock);
        $validator->initialize($executionContextMock);
        $validator->validate('foo', new Locale());
    }

    /**
     * @return array{
     *      0: \PHPUnit\Framework\MockObject\MockObject&ConstraintViolationBuilderInterface,
     *      1: \PHPUnit\Framework\MockObject\MockObject&ExecutionContext,
     *      2: \PHPUnit\Framework\MockObject\MockObject&Localization,
     *  }
     */
    private function getMocks(): array
    {
        return [
            $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock(),
            $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock(),
        ];
    }
}
