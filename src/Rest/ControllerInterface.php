<?php
declare(strict_types=1);
/**
 * /src/Rest/ControllerInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest;

/**
 * Interface ControllerInterface
 *
 * @package App\Rest
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface ControllerInterface
{
    /**
     * Controller constructor.
     *
     * @param ResourceInterface       $resource
     * @param ResponseHelperInterface $responseHelper
     */
    public function __construct(ResourceInterface $resource, ResponseHelperInterface $responseHelper);

    /**
     * @return ResourceInterface
     */
    public function getResource(): ResourceInterface;

    /**
     * @return ResponseHelperInterface
     */
    public function getResponseHelper(): ResponseHelperInterface;
}
