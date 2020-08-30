<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Authenticated/IdsAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Authenticated;

use App\Rest\Traits\Methods\IdsMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait IdsAction
 *
 * Trait to add 'idsAction' for REST controllers for authenticated users.
 *
 * @see \App\Rest\Traits\Methods\IdsMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Authenticated
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait IdsAction
{
    use IdsMethod;

    /**
     * @Route(
     *     path="/ids",
     *     methods={"GET"},
     *  )
     *
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     *
     * @throws Throwable
     */
    public function idsAction(Request $request): Response
    {
        return $this->idsMethod($request);
    }
}
