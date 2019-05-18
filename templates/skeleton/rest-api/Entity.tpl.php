<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /src/Entity/<?php echo $entityName ?>.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Entity;

use App\Entity\Traits\Blameable;
use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class <?php echo $entityName . "\n" ?>
 *
 * @ORM\Table(
 *      name="<?php echo $tableName ?>",
 *  )
 * @ORM\Entity()
 *
 * @package App\Entity
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $entityName ?> implements EntityInterface
{
    // Traits
    use Blameable;
    use Timestampable;

    /**
     * @var string
     *
     * @Groups({
     *      "<?php echo $entityName ?>",
     *      "<?php echo $entityName ?>.id",
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
     * <?php echo $entityName ?> constructor.
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
