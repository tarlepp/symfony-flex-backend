<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/TimezoneValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Service\Localization;
use App\Validator\Constraints\Timezone;
use App\Validator\Constraints\TimezoneValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Class TimezoneValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class TimezoneValidatorTest extends KernelTestCase
{
    /**
     * @testdox Test that `TimezoneValidator::validate` method calls expected service methods
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        // Create new user
        $user = (new User())
            ->setTimezone('foo/bar');

        [$constraintViolationBuilderMock, $executionContextMock, $localizationMock] = $this->getMocks();

        $localizationMock
            ->expects(static::once())
            ->method('getTimezones')
            ->willReturn(['bar/foo']);

        $executionContextMock
            ->expects(static::once())
            ->method('buildViolation')
            ->with(Timezone::MESSAGE)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('setParameter')
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('setCode')
            ->with(Timezone::INVALID_TIMEZONE)
            ->willReturn($constraintViolationBuilderMock);

        $constraintViolationBuilderMock
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new TimezoneValidator($localizationMock);
        $validator->initialize($executionContextMock);
        $validator->validate($user, new Timezone());
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
