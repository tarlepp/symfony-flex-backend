<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Voter/IsUserHimselfVoterTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Security\Voter;

use App\Entity\User;
use App\Security\SecurityUser;
use App\Security\Voter\IsUserHimselfVoter;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class IsUserHimselfVoterTest
 *
 * @package App\Tests\Integration\Security\Voter
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class IsUserHimselfVoterTest extends KernelTestCase
{
    private IsUserHimselfVoter $voter;

    /**
     * @testdox Test that `vote` method returns `Voter::ACCESS_ABSTAIN` when subject is not supported
     */
    public function testThatVoteReturnsExpectedIfSubjectIsNotSupported(): void
    {
        $token = new AnonymousToken('secret', 'anon');

        static::assertSame(Voter::ACCESS_ABSTAIN, $this->voter->vote($token, 'subject', ['IS_USER_HIMSELF']));
    }

    /**
     * @testdox Test that `vote` method returns `Voter::ACCESS_DENIED` when subject mismatch with given token user
     */
    public function testThatVoteReturnsExpectedWhenUserMismatch(): void
    {
        $token = new JWTUserToken([], new SecurityUser(new User()));

        static::assertSame(Voter::ACCESS_DENIED, $this->voter->vote($token, new User(), ['IS_USER_HIMSELF']));
    }

    /**
     * @testdox Test that `vote` method returns `Voter::ACCESS_GRANTED` when subject match with given token user
     */
    public function testThatVoteReturnsExpectedWhenUserMatch(): void
    {
        $user = new User();
        $token = new JWTUserToken([], new SecurityUser($user));

        static::assertSame(Voter::ACCESS_GRANTED, $this->voter->vote($token, $user, ['IS_USER_HIMSELF']));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->voter = new IsUserHimselfVoter();
    }
}
