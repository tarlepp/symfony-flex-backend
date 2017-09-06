<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Methods/PatchMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\RestResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait PatchMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method RestResourceInterface    getResource()
 * @method ResponseHandlerInterface getResponseHandler()
 * @method FormInterface            processForm()
 */
trait PatchMethod
{
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
        $allowedHttpMethods = $allowedHttpMethods ?? ['PATCH'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        try {
            $data = $this
                ->getResource()
                ->update($id, $this->processForm($request, $formFactory, __METHOD__, $id)->getData(), true);

            return $this
                ->getResponseHandler()
                ->createResponse($request, $data);
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
