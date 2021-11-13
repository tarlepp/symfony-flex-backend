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
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Class LanguageValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LanguageValidatorTest extends KernelTestCase
{
    /**
     * @testdox Test that `LanguageValidator::validate` method calls expected service methods
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        $localizationMock = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
        $contextMock = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $builderMock = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();

        $localizationMock
            ->expects(self::once())
            ->method('getLanguages')
            ->willReturn(['bar']);

        $contextMock
            ->expects(self::once())
            ->method('buildViolation')
            ->with(Language::MESSAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects(self::once())
            ->method('setParameter')
            ->willReturn($builderMock);

        $builderMock
            ->expects(self::once())
            ->method('setCode')
            ->with(Language::INVALID_LANGUAGE)
            ->willReturn($builderMock);

        $builderMock
            ->expects(self::once())
            ->method('addViolation');

        // Run validator
        $validator = new LanguageValidator($localizationMock);
        $validator->initialize($contextMock);
        $validator->validate('foo', new Language());
    }
}
