<?php
declare(strict_types = 1);

namespace App\Tests\Integration\Rest\src;

use App\Rest\Controller;
use App\Rest\Interfaces\RestResourceInterface;

abstract class AbstractController extends Controller
{
    public function __construct(RestResourceInterface $resource)
    {
        $this->resource = $resource;
    }
}
