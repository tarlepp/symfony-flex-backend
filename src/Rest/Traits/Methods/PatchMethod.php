<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/PatchMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait PatchMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait PatchMethod
{
    // Traits
    use PatchUpdateMethod;

    /**
     * Generic 'patchMethod' method for REST resources.
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param string               $id
     * @param array|null           $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function patchMethod(
        Request $request,
        FormFactoryInterface $formFactory,
        string $id,
        array $allowedHttpMethods = null
    ): Response {
        return $this->patchUpdateMethod($request, $formFactory, $id, $allowedHttpMethods ?? ['PATCH']);
    }
}
