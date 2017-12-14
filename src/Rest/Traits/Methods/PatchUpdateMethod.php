<?php
declare(strict_types = 1);
/**
 * /src/Rest/Traits/Methods/PatchUpdateMethod.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits\Methods;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait PatchUpdateMethod
 *
 * @package App\Rest\Traits\Methods
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait PatchUpdateMethod
{
    // Traits
    use AbstractFormMethods;
    use AbstractGenericMethods;

    /**
     * Wrapped method that PatchMethod and UpdateMethod are using, only difference between those is used default HTTP
     * method - PATCH vs PUT
     *
     * @param Request              $request
     * @param FormFactoryInterface $formFactory
     * @param string               $id
     * @param array                $allowedHttpMethods
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function patchUpdateMethod(
        Request $request,
        FormFactoryInterface $formFactory,
        string $id,
        array $allowedHttpMethods
    ): Response {
        // Make sure that we have everything we need to make this work
        $this->validateRestMethod($request, $allowedHttpMethods);

        try {
            $data = $this
                ->getResource()
                ->update($id, $this->processForm($request, $formFactory, __METHOD__, $id)->getData(), true);

            return $this->getResponseHandler()->createResponse($request, $data);
        } catch (\Exception $exception) {
            throw $this->handleRestMethodException($exception);
        }
    }
}
