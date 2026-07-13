<?php
declare(strict_types = 1);

/**
 * /src/Validator/Constraints/TimezoneValidatorTest.php
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Service\Localization;
use App\Validator\Constraints\Timezone;
use App\Validator\Constraints\TimezoneValidator;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class TimezoneValidatorTest extends KernelTestCase
{
    #[TestDox('Test that `TimezoneValidator::validate` method calls expected service methods')]
    public function testThatValidateCallsExpectedMethods(): void
    {
        $localizationMock = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
        $contextMock = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();
        $builderMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();

        $localizationMock
            ->expects($this->once())
            ->method('getTimezones')
            ->willReturn(['bar/foo']);

        $contextMock
            ->expects($this->once())
            ->method('buildViolation')
            ->with(Timezone::MESSAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('setParameter')
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('setCode')
            ->with(Timezone::INVALID_TIMEZONE)
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('addViolation');

        // Run validator
        new TimezoneValidator($localizationMock)
            ->validateInContext('foo/bar', new Timezone(), $contextMock);
    }
}
