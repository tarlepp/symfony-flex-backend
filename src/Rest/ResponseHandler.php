<?php
declare(strict_types = 1);
/**
 * /src/Rest/ResponseHandler.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Rest;

use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_pop;
use function count;
use function end;
use function explode;
use function implode;
use function sprintf;
use function strncmp;

/**
 * Class ResponseHandler
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class ResponseHandler implements ResponseHandlerInterface
{
    /**
     * Content types for supported response output formats.
     *
     * @var array<string, string>
     */
    private array $contentTypes = [
        self::FORMAT_JSON => 'application/json',
        self::FORMAT_XML => 'application/xml',
    ];

    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws Throwable
     */
    public function getSerializeContext(Request $request, ?RestResourceInterface $restResource = null): array
    {
        /**
         * Specify used populate settings
         *
         * @var array<int, string>
         */
        $populate = (array)$request->get('populate', []);

        $groups = array_merge(['default', $populate]);

        if ($restResource !== null) {
            // Get current entity name
            $bits = explode('\\', $restResource->getEntityName());
            $entityName = end($bits);

            $populate = $this->checkPopulateAll(
                array_key_exists('populateAll', $request->query->all()),
                $populate,
                $entityName,
                $restResource
            );

            $groups = array_merge([$entityName], $populate);
            $filter = static fn (string $groupName): bool => strncmp($groupName, 'Set.', 4) === 0;

            if (array_key_exists('populateOnly', $request->query->all())
                || count(array_filter($groups, $filter)) > 0
            ) {
                $groups = count($populate) === 0 ? [$entityName] : $populate;
            }
        }

        return array_merge(
            ['groups' => $groups],
            $restResource !== null ? $restResource->getSerializerContext() : [],
        );
    }

    /**
     * @throws Throwable
     */
    public function createResponse(
        Request $request,
        mixed $data,
        ?RestResourceInterface $restResource = null,
        ?int $httpStatus = null,
        ?string $format = null,
        ?array $context = null,
    ): Response {
        $httpStatus ??= 200;
        $context ??= $this->getSerializeContext($request, $restResource);
        $format = $this->getFormat($request, $format);
        $response = $this->getResponse($data, $httpStatus, $format, $context);

        // Set content type
        $response->headers->set('Content-Type', $this->contentTypes[$format]);

        return $response;
    }

    public function handleFormError(FormInterface $form): void
    {
        $errors = [];

        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $name = $error->getOrigin()?->getName() ?? '';

            $errors[] = sprintf(
                'Field \'%s\': %s',
                $name,
                $error->getMessage()
            );

            if ($name === '') {
                array_pop($errors);

                $errors[] = $error->getMessage();
            }
        }

        throw new HttpException(Response::HTTP_BAD_REQUEST, implode("\n", $errors));
    }

    /**
     * @param array<int, string> $populate
     *
     * @return array<int, string>
     *
     * @throws Throwable
     */
    private function checkPopulateAll(
        bool $populateAll,
        array $populate,
        string $entityName,
        RestResourceInterface $restResource,
    ): array {
        // Set all associations to be populated
        if ($populateAll && count($populate) === 0) {
            $associations = $restResource->getAssociations();
            $populate = array_map(
                static fn (string $assocName): string => $entityName . '.' . $assocName,
                $associations,
            );
        }

        return $populate;
    }

    /**
     * Getter method response format with fallback to default formats;
     *  - XML
     *  - JSON
     */
    private function getFormat(Request $request, ?string $format = null): string
    {
        return $format ?? ($request->getContentType() === self::FORMAT_XML ? self::FORMAT_XML : self::FORMAT_JSON);
    }

    /**
     * @param array<int|string, mixed> $context
     *
     * @throws HttpException
     */
    private function getResponse(mixed $data, int $httpStatus, string $format, array $context): Response
    {
        try {
            // Create new response
            $response = new Response();
            $response->setContent($this->serializer->serialize($data, $format, $context));
            $response->setStatusCode($httpStatus);
        } catch (Throwable $exception) {
            $status = Response::HTTP_BAD_REQUEST;

            throw new HttpException($status, $exception->getMessage(), $exception, [], $status);
        }

        return $response;
    }
}
