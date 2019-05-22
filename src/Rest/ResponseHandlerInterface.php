<?php
declare(strict_types = 1);
/**
 * /src/Rest/ResponseHandlerInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Interface ResponseHandlerInterface
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ResponseHandlerInterface
{
    /**
     * Constants for response output formats.
     */
    public const FORMAT_JSON = 'json';
    public const FORMAT_XML = 'xml';

    /**
     * ResponseHandler constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer);

    /**
     * Getter for serializer
     *
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface;

    /**
     * Helper method to get serialization context for request.
     *
     * @param Request                    $request
     * @param RestResourceInterface|null $restResource
     *
     * @return mixed[]
     */
    public function getSerializeContext(Request $request, ?RestResourceInterface $restResource = null): array;

    /**
     * Helper method to create response for request.
     *
     * @param Request                    $request
     * @param mixed                      $data
     * @param RestResourceInterface|null $restResource
     * @param int|null                   $httpStatus
     * @param string|null                $format
     * @param mixed[]|null               $context
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function createResponse(
        Request $request,
        $data,
        ?RestResourceInterface $restResource = null,
        ?int $httpStatus = null,
        ?string $format = null,
        ?array $context = null
    ): Response;

    /**
     * Method to handle form errors.
     *
     * @param FormInterface $form
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handleFormError(FormInterface $form): void;
}
