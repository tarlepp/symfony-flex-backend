<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/LanguageValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Service\Localization;
use App\Validator\Constraints\Language;
use App\Validator\Constraints\LanguageValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;

/**
 * Class LanguageValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LanguageValidatorTest extends KernelTestCase
{
    private Language $constraint;

    /**
     * @var MockObject|ExecutionContext
     */
    private $context;

    /**
     * @var MockObject|ConstraintViolationBuilderInterface
     */
    private $builder;

    /**
     * @var MockObject|Localization
     */
    private $localization;

    /**
     * @throws Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->constraint = new Language();
        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $this->localization = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
    }

    public function testThatValidateCallsExpectedMethods(): void
    {
        $this->localization
            ->expects(static::once())
            ->method('getLanguages')
            ->willReturn(['bar']);

        $this->context
            ->expects(static::once())
            ->method('buildViolation')
            ->with(Language::MESSAGE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setParameter')
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setCode')
            ->with(Language::INVALID_LANGUAGE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new LanguageValidator($this->localization);
        $validator->initialize($this->context);
        $validator->validate('foo', $this->constraint);
    }
}
