<?php
declare(strict_types=1);
/**
 * /src/Rest/ResponseHelperInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Interface ResponseHelperInterface
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ResponseHelperInterface
{
    /**
     * Constants for response output formats.
     *
     * @var string
     */
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';

    /**
     * ResponseHelper constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer);

    /**
     * Getter for current resource service
     *
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface;

    /**
     * Setter for resource service.
     *
     * @param ResourceInterface $resource
     *
     * @return ResponseHelperInterface
     */
    public function setResource(ResourceInterface $resource): ResponseHelperInterface;

    /**
     * Helper method to get serialization context for request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function getSerializeContext(Request $request): array;

    /**
     * Helper method to create response for request.
     *
     * @param Request      $request
     * @param mixed        $data
     * @param null|integer $httpStatus
     * @param null|string  $format
     * @param null|array   $context
     *
     * @return Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function createResponse(
        Request $request,
        $data,
        int $httpStatus = null,
        string $format = null,
        array $context = null
    ): Response;
}