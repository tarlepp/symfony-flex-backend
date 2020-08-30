<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Root/FindAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Root;

use App\Rest\Traits\Methods\FindMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait FindAction
 *
 * Trait to add 'findAction' for REST controllers for 'ROLE_ROOT' users.
 *
 * @see \App\Rest\Traits\Methods\FindMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Root
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait FindAction
{
    use FindMethod;

    /**
     * @Route(
     *      path="",
     *      methods={"GET"},
     *  )
     *
     * @Security("is_granted('ROLE_ROOT')")
     *
     * @throws Throwable
     */
    public function findAction(Request $request): Response
    {
        return $this->findMethod($request);
    }
}
