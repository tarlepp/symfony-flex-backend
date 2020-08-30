<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/src\AbstractController.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\src;

use App\Rest\Controller;
use App\Rest\Interfaces\RestResourceInterface;

/**
 * Class AbstractController
 *
 * @package App\Tests\Integration\Rest\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class AbstractController extends Controller
{
    /**
     * AbstractController constructor.
     */
    public function __construct(RestResourceInterface $resource)
    {
        $this->resource = $resource;
    }
}
