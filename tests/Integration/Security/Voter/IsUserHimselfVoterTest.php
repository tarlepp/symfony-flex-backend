<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Security/Voter/IsUserHimselfVoterTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\Security\Voter;

use App\Security\Voter\IsUserHimselfVoter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class IsUserHimselfVoterTest
 *
 * @package App\Tests\Integration\Security\Voter
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class IsUserHimselfVoterTest extends KernelTestCase
{
    public function testThatVoteReturnsExpectedIfVoterIsNotSupported(): void
    {
        $voter = new IsUserHimselfVoter();
        $token = new AnonymousToken('secret', 'anon');

        static::assertSame(Voter::ACCESS_ABSTAIN, $voter->vote($token, 'subject', []));
    }
}
