<?php
declare(strict_types = 1);

/**
 * /src/Validator/Constraints/LocaleValidatorTest.php
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Service\Localization;
use App\Validator\Constraints\Locale;
use App\Validator\Constraints\LocaleValidator;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class LocaleValidatorTest extends KernelTestCase
{
    #[TestDox('Test that `LocaleValidator::validate` method calls expected service methods')]
    public function testThatValidateCallsExpectedMethods(): void
    {
        $localizationMock = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
        $contextMock = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();
        $builderMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();

        $localizationMock
            ->expects($this->once())
            ->method('getLocales')
            ->willReturn(['bar']);

        $contextMock
            ->expects($this->once())
            ->method('buildViolation')
            ->with(Locale::MESSAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('setParameter')
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('setCode')
            ->with(Locale::INVALID_LOCALE)
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('addViolation');

        // Run validator
        new LocaleValidator($localizationMock)
            ->validateInContext('foo', new Locale(), $contextMock);
    }
}
