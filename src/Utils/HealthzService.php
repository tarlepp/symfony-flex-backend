<?php
declare(strict_types = 1);
/**
 * /src/Utils/HealthzService.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;
use Throwable;

/**
 * Class HealthzService
 *
 * @package App\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class HealthzService
{
    private HealthzRepository $repository;

    /**
     * HealthzService constructor.
     */
    public function __construct(HealthzRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Method to check that "all" is ok within our application. This will try
     * to do following:
     *  1) Remove data from database
     *  2) Create data to database
     *  3) Read data from database
     *
     * These steps should make sure that at least application database is
     * working as expected.
     *
     * @throws Throwable
     */
    public function check(): ?Healthz
    {
        $this->repository->cleanup();
        $this->repository->create();

        return $this->repository->read();
    }
}
