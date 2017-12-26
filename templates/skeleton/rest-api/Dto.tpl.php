<?= "<?php\n" ?>
declare(strict_types=1);
/**
* /src/DTO/<?= $entityName ?>.php
*
* @author  <?= $author . "\n" ?>
*/
namespace App\DTO;

use App\Entity\EntityInterface;
use App\Entity\<?= $entityName ?> as <?= $entityName ?>Entity;

/**
 * Class <?= $entityName . "\n" ?>
 *
 * @package App\DTO
 * @author  <?= $author . "\n" ?>
 */
class <?= $entityName ?> extends RestDto
{
    /**
     * @var string|null
     */
    protected $id;

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface||<?= $entityName ?>Entity $entity
     *
     * @return RestDtoInterface|<?= $entityName . "\n" ?>
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        $this->id = $entity->getId();
    }

    /**
     * @return null|string
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param null|string $id
     *
     * @return <?= $entityName . "\n" ?>
     */
    public function setId(string $id = null): <?= $entityName . "\n" ?>
    {
        $this->setVisited('id');

        $this->id = $id;

        return $this;
    }
}
