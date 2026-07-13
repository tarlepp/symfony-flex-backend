<?php
declare(strict_types = 1);

/**
 * /src/Utils/HealthzService.php
 */

namespace App\Utils;

use App\Entity\Healthz;
use App\Repository\HealthzRepository;
use Throwable;

readonly class HealthzService
{
    public function __construct(
        private HealthzRepository $repository,
    ) {
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
