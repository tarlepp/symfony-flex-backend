<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/LocaleValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Service\Localization;
use App\Validator\Constraints\Locale;
use App\Validator\Constraints\LocaleValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;

/**
 * Class LocaleValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LocaleValidatorTest extends KernelTestCase
{
    private Locale $constraint;

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

        $this->constraint = new Locale();
        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $this->localization = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
    }

    public function testThatValidateCallsExpectedMethods(): void
    {
        $this->localization
            ->expects(static::once())
            ->method('getLocales')
            ->willReturn(['bar']);

        $this->context
            ->expects(static::once())
            ->method('buildViolation')
            ->with(Locale::MESSAGE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setParameter')
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setCode')
            ->with(Locale::INVALID_LOCALE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new LocaleValidator($this->localization);
        $validator->initialize($this->context);
        $validator->validate('foo', $this->constraint);
    }
}
