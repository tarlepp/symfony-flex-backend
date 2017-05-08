<?php
declare(strict_types=1);
/**
 * /src/Entity/Interfaces/EntityInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity\Interfaces;

/**
 * Interface EntityInterface
 *
 * @package App\Entity\Interfaces
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface EntityInterface
{
    /**
     * @return string
     */
    public function getId(): string;
}
