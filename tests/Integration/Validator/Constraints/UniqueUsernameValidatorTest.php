<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Validator/Constraints/UniqueUsernameValidatorTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueUsername;
use App\Validator\Constraints\UniqueUsernameValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Throwable;
use function assert;

/**
 * Class UniqueUsernameValidatorTest
 *
 * @package App\Tests\Integration\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UniqueUsernameValidatorTest extends KernelTestCase
{
    private ?MockObject $builder = null;
    private MockObject | ExecutionContext | null $context = null;
    private MockObject | UserRepository | null $repository = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $this->context = $this->getMockBuilder(ExecutionContext::class)->disableOriginalConstructor()->getMock();
        $this->repository = $this->getMockBuilder(UserRepository::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @throws Throwable
     *
     * @testdox Test that `UniqueEmailValidator::validate` method calls expected service methods
     */
    public function testThatValidateCallsExpectedMethods(): void
    {
        // Create new user
        $user = (new User())
            ->setUsername('john');

        $this->getRepositoryMock()
            ->expects(static::once())
            ->method('isUsernameAvailable')
            ->with($user->getUsername(), $user->getId())
            ->willReturn(false);

        $this->getContextMock()
            ->expects(static::once())
            ->method('buildViolation')
            ->with(UniqueUsername::MESSAGE)
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('setCode')
            ->with(UniqueUsername::IS_UNIQUE_USERNAME_ERROR)
            ->willReturn($this->builder);

        $this->getBuilderMock()
            ->expects(static::once())
            ->method('addViolation');

        // Run validator
        $validator = new UniqueUsernameValidator($this->getRepository());
        $validator->initialize($this->getContext());
        $validator->validate($user, new UniqueUsername());
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

    private function getRepository(): UserRepository
    {
        assert($this->repository instanceof UserRepository);

        return $this->repository;
    }

    private function getRepositoryMock(): MockObject
    {
        assert($this->repository instanceof MockObject);

        return $this->repository;
    }
}
