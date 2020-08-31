<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/TimezoneValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Service\Localization;
use App\Validator\Constraints\Timezone;
use App\Validator\Constraints\TimezoneValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;

/**
 * Class TimezoneValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class TimezoneValidatorTest extends KernelTestCase
{
    private Timezone $constraint;

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

        $this->constraint = new Timezone();
        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $this->localization = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
    }

    public function testThatValidateCallsExpectedMethods(): void
    {
        // Create new user
        $user = (new User())
            ->setTimezone('foo/bar');

        $this->localization
            ->expects(static::once())
            ->method('getTimezones')
            ->willReturn(['bar/foo']);

        $this->context
            ->expects(static::once())
            ->method('buildViolation')
            ->with(Timezone::MESSAGE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setParameter')
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('setCode')
            ->with(Timezone::INVALID_TIMEZONE)
            ->willReturn($this->builder);

        $this->builder
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new TimezoneValidator($this->localization);
        $validator->initialize($this->context);
        $validator->validate($user, $this->constraint);
    }
}
