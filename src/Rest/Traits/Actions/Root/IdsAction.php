<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Actions/Root/IdsAction.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest\Traits\Actions\Root;

use App\Rest\Traits\Methods\IdsMethod;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * Trait IdsAction
 *
 * Trait to add 'idsAction' for REST controllers for 'ROLE_ROOT' users.
 *
 * @see \App\Rest\Traits\Methods\IdsMethod for detailed documents.
 *
 * @package App\Rest\Traits\Actions\Root
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait IdsAction
{
    use IdsMethod;

    /**
     * @Route(
     *      path="/ids",
     *      methods={"GET"},
     *  )
     *
     * @Security("is_granted('ROLE_ROOT')")
     *
     * @throws Throwable
     */
    public function idsAction(Request $request): Response
    {
        return $this->idsMethod($request);
    }
}
