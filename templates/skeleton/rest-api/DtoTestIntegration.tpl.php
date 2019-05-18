<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/<?php echo $entityName ?>Test.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Tests\Integration\DTO;

use App\DTO\<?php echo $entityName ?> as <?php echo $entityName ?>Dto;
use App\Entity\<?php echo $entityName ?> as <?php echo $entityName ?>Entity;

/**
 * Class <?php echo $entityName ?>Test
 *
 * @package App\Tests\Integration\DTO
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $entityName ?>Test extends DtoTestCase
{
    protected $dtoClass = <?php echo $entityName ?>Dto::class;

    public function testThatLoadMethodWorks(): void
    {
        // Create entity
        $entity = new <?php echo $entityName ?>Entity();

        // Create DTO and load entity
        $dto = new <?php echo $entityName ?>Dto();
        $dto->load($entity);

        static::assertSame($entity->getId(), $dto->getId());
    }
}
