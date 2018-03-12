<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/UpdateMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use LogicException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Trait UpdateMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait UpdateMethod
{
    // Traits
    use AbstractFormMethods;
    use AbstractGenericMethods;

    /**
     * Generic 'updateMethod' method for REST resources.
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param string               $id
     * @param string[]|null        $allowedHttpMethods
     *
     * @return Response
     *
     * @throws LogicException
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    public function updateMethod(
        Request $request,
        FormFactoryInterface $formFactory,
        string $id,
        ?array $allowedHttpMethods = null
    ): Response {
        $allowedHttpMethods = $allowedHttpMethods ?? ['PUT'];

        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        try {
            $data = $this
                ->getResource()
                ->update($id, $this->processForm($request, $formFactory, __METHOD__, $id)->getData(), true);

            return $this->getResponseHandler()->createResponse($request, $data);
        } catch (Throwable $exception) {
            throw $this->handleRestMethodException($exception, $id);
        }
    }
}
