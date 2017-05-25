<?php
declare(strict_types=1);
/**
 * /src/Entity/EntityInterface.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Entity;

/**
 * Interface EntityInterface
 *
 * @package App\Entity
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
interface EntityInterface
{
    /**
     * @return string
     */
    public function getId(): string;
}
