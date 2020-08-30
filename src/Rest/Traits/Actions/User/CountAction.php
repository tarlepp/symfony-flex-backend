<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/User/CountAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\User;

use App\Rest\Traits\Methods\CountMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait CountAction
 *
 * Trait to add 'countAction' for REST controllers for 'ROLE_USER' users.
 *
 * @see \App\Rest\Traits\Methods\CountMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\User
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait CountAction
{
    use CountMethod;

    /**
     * @Route(
     *      path="/count",
     *      methods={"GET"},
     *  )
     *
     * @Security("is_granted('ROLE_USER')")
     *
     * @throws Throwable
     */
    public function countAction(Request $request): Response
    {
        return $this->countMethod($request);
    }
}
