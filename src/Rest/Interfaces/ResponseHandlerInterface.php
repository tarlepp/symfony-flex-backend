<?php
declare(strict_types = 1);
/**
 * /src/Rest/Interfaces/ResponseHandlerInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest\Interfaces;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface ResponseHandlerInterface
{
    /**
     * Constants for response output formats.
     */
    // @codeCoverageIgnoreStart
    public const string FORMAT_JSON = 'json';
    public const string FORMAT_XML = 'xml';
    // @codeCoverageIgnoreEnd

    public function __construct(SerializerInterface $serializer);

    /**
     * Getter for serializer
     */
    public function getSerializer(): SerializerInterface;

    /**
     * Helper method to get serialization context for request.
     *
     * @return array<int|string, mixed>
     */
    public function getSerializeContext(Request $request, ?RestResourceInterface $restResource = null): array;

    /**
     * Helper method to create response for request.
     *
     * @codeCoverageIgnore This is needed because variables are multiline
     *
     * @param array<int|string, bool|array<int, string>>|null $context
     *
     * @throws HttpException
     */
    public function createResponse(
        Request $request,
        mixed $data,
        ?RestResourceInterface $restResource = null,
        ?int $httpStatus = null,
        ?string $format = null,
        ?array $context = null,
    ): Response;

    /**
     * Method to handle form errors.
     *
     * @throws HttpException
     */
    public function handleFormError(FormInterface $form): void;
}
