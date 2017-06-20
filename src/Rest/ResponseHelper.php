<?php
declare(strict_types=1);
/**
 * /src/Rest/ResponseHelper.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class ResponseHelper
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class ResponseHelper implements ResponseHelperInterface
{
    /**
     * Content types for supported response output formats.
     *
     * @var array
     */
    private $contentTypes = [
        self::FORMAT_JSON   => 'application/json',
        self::FORMAT_XML    => 'application/xml'
    ];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ResourceInterface
     */
    private $resource;

    /**
     * ResponseHelper constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Getter for current resource service
     *
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface
    {
        return $this->resource;
    }

    /**
     * Setter for resource service.
     *
     * @param ResourceInterface $resource
     *
     * @return ResponseHelperInterface
     */
    public function setResource(ResourceInterface $resource): ResponseHelperInterface
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Helper method to get serialization context for request.
     *
     * @param Request $request
     *
     * @return array
     */
    public function getSerializeContext(Request $request): array
    {
        // Specify used populate settings
        $populate = (array)$request->get('populate', []);
        $populateAll = \array_key_exists('populateAll', $request->query->all());
        $populateOnly = \array_key_exists('populateOnly', $request->query->all());

        // Get current entity name
        $entityName = $this->getResource()->getEntityName();

        $bits = \explode('\\', $entityName);
        $entityName = \end($bits);

        // Determine used default group
        $defaultGroup = $populateAll ? 'Default' : $entityName;

        // Set all associations to be populated
        if ($populateAll && \count($populate) === 0) {
            $associations = $this->getResource()->getAssociations();

            $iterator = function (string $assocName) use ($entityName): string {
                return $entityName . '.' . $assocName;
            };

            $populate = \array_map($iterator, $associations);
        }

        if ($populateOnly) {
            $groups = \count($populate) === 0 ? [$defaultGroup] : $populate;
        } else {
            $groups = \array_merge([$defaultGroup], $populate);
        }

        return [
            'groups' => $groups,
        ];
    }

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
    ): Response
    {
        $httpStatus = $httpStatus ?? 200;
        $format = $format ?? $request->getContentType() === self::FORMAT_XML ? self::FORMAT_XML : self::FORMAT_JSON;
        $context = $context ?? $this->getSerializeContext($request);

        try {
            // Create new response
            $response = new Response();
            $response->setContent($this->serializer->serialize($data, $format, $context));
            $response->setStatusCode($httpStatus);
        } catch (\Exception $error) {
            throw new HttpException(
                Response::HTTP_BAD_REQUEST,
                $error->getMessage(),
                $error,
                [],
                Response::HTTP_BAD_REQUEST
            );
        }

        // Set content type
        $response->headers->set('Content-Type', $this->contentTypes[$format]);

        return $response;
    }
}
