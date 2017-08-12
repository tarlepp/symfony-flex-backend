<?php
declare(strict_types = 1);
/**
 * /src/Utils/HealthzService.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils;

use App\Repository\HealthzRepository;

/**
 * Class HealthzService
 *
 * @package App\Utils
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class HealthzService
{
    /**
     * @var HealthzRepository
     */
    private $repository;

    /**
     * HealthzService constructor.
     *
     * @param HealthzRepository $repository
     */
    public function __construct(HealthzRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    public function check(): string
    {
        $this->repository->cleanup();
        $this->repository->create();

        return $this->repository->read();
    }
}
