<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Methods/CreateMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use App\Rest\ResourceInterface;
use App\Rest\ResponseHandlerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait CreateMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 *
 * @method ResourceInterface getResource()
 * @method ResponseHandlerInterface getResponseHandler()
 * @method FormInterface processForm()
 */
trait CreateMethod
{
    /**
     * Generic 'createMethod' method for REST resources.
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param array|null           $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function createMethod(
        Request $request,
        FormFactoryInterface $formFactory,
        array $allowedHttpMethods = null
    ): Response
    {
        $allowedHttpMethods = $allowedHttpMethods ?? ['POST'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        try {
            $data = $this
                ->getResource()
                ->create($this->processForm($request, $formFactory, __METHOD__)->getData(), true);

            return $this
                ->getResponseHandler()
                ->createResponse($request, $data, Response::HTTP_CREATED);
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
