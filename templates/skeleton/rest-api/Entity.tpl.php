<?= "<?php\n" ?>
declare(strict_types = 1);
/**
 * /src/Entity/<?= $entityName ?>.php
 *
 * @author  <?= $author . "\n" ?>
 */
namespace App\Entity;

use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class <?= $entityName . "\n" ?>
 *
 * @ORM\Table(
 *      name="<?= $tableName ?>",
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author  <?= $author . "\n" ?>
 */
class <?= $entityName ?> implements EntityInterface
{
    // Traits
    use Blameable;
    use Timestampable;

    /**
     * @var string
     *
     * @Groups({
     *      "<?= $entityName ?>",
     *      "<?= $entityName ?>.id",
     *  })
     *
     * @ORM\Column(
     *      name="id",
     *      type="guid",
     *      nullable=false,
     *  )
     * @ORM\Id()
     */
    private $id;

    /**
     * <?= $entityName ?> constructor.
     */
    public function __construct()
    {
        $this->id = Uuid::uuid4()->toString();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }
}
