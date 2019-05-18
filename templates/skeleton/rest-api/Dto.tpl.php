<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
* /src/DTO/<?php echo $entityName ?>.php
*
* @author  <?php echo $author . "\n" ?>
*/
namespace App\DTO;

use App\Entity\EntityInterface;
use App\Entity\<?php echo $entityName ?> as <?php echo $entityName ?>Entity;

/**
 * Class <?php echo $entityName . "\n" ?>
 *
 * @package App\DTO
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $entityName ?> extends RestDto
{
    /**
     * @var string|null
     */
    protected $id;

    /**
     * Method to load DTO data from specified entity.
     *
     * @param EntityInterface||<?php echo $entityName ?>Entity $entity
     *
     * @return RestDtoInterface|<?php echo $entityName . "\n" ?>
     */
    public function load(EntityInterface $entity): RestDtoInterface
    {
        $this->id = $entity->getId();

        return $this;
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
     * @return <?php echo $entityName . "\n" ?>
     */
    public function setId(string $id = null): <?php echo $entityName . "\n" ?>
    {
        $this->setVisited('id');

        $this->id = $id;

        return $this;
    }
}
