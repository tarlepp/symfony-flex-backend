<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/LanguageValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Service\Localization;
use App\Validator\Constraints\Language;
use App\Validator\Constraints\LanguageValidator;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class LanguageValidatorTest extends KernelTestCase
{
    #[TestDox('Test that `LanguageValidator::validate` method calls expected service methods')]
    public function testThatValidateCallsExpectedMethods(): void
    {
        $localizationMock = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
        $contextMock = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $builderMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();

        $localizationMock
            ->expects($this->once())
            ->method('getLanguages')
            ->willReturn(['bar']);

        $contextMock
            ->expects($this->once())
            ->method('buildViolation')
            ->with(Language::MESSAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('setParameter')
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('setCode')
            ->with(Language::INVALID_LANGUAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects($this->once())
            ->method('addViolation');

        // Run validator
        $validator = new LanguageValidator($localizationMock);
        $validator->initialize($contextMock);
        $validator->validate('foo', new Language());
    }
}
