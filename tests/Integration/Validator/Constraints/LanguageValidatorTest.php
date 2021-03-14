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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use function assert;

/**
 * Class LanguageValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LanguageValidatorTest extends KernelTestCase
{
    private ?MockObject $builder = null;
    private MockObject | ExecutionContext | null $context = null;
    private MockObject | Localization | null $localization = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $this->localization = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @testdox Test that `LanguageValidator::validate` method calls expected service methods
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        $this->getLocalizationMock()
            ->expects(static::once())
            ->method('getLanguages')
            ->willReturn(['bar']);

        $this->getContextMock()
            ->expects(static::once())
            ->method('buildViolation')
            ->with(Language::MESSAGE)
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('setParameter')
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('setCode')
            ->with(Language::INVALID_LANGUAGE)
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new LanguageValidator($this->getLocalization());
        $validator->initialize($this->getContext());
        $validator->validate('foo', new Language());
    }

    private function getLocalization(): Localization
    {
        assert($this->localization instanceof Localization);

        return $this->localization;
    }

    private function getLocalizationMock(): MockObject
    {
        assert($this->localization instanceof MockObject);

        return $this->localization;
    }

    private function getContext(): ExecutionContext
    {
        assert($this->context instanceof ExecutionContext);

        return $this->context;
    }

    private function getContextMock(): MockObject
    {
        assert($this->context instanceof MockObject);

        return $this->context;
    }

    private function getBuilderMock(): MockObject
    {
        assert($this->builder instanceof MockObject);

        return $this->builder;
    }
}
