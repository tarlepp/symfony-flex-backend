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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use function assert;

/**
 * Class TimezoneValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class TimezoneValidatorTest extends KernelTestCase
{
    private ?MockObject $builder = null;
    private MockObject | ExecutionContext | null $context = null;
    private MockObject | Localization | null $localization = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->localization = $this->getMockBuilder(Localization::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @testdox Test that `TimezoneValidator::validate` method calls expected service methods
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        // Create new user
        $user = (new User())
            ->setTimezone('foo/bar');

        $this->getLocalizationMock()
            ->expects(static::once())
            ->method('getTimezones')
            ->willReturn(['bar/foo']);

        $this->getContextMock()
            ->expects(static::once())
            ->method('buildViolation')
            ->with(Timezone::MESSAGE)
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('setParameter')
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('setCode')
            ->with(Timezone::INVALID_TIMEZONE)
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new TimezoneValidator($this->getLocalization());
        $validator->initialize($this->getContext());
        $validator->validate($user, new Timezone());
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
