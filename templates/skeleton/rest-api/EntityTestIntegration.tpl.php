<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Integration/Entity//<?php echo $entityName ?>Test.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\<?php echo $entityName ?>;

/**
 * Class <?php echo $entityName ?>Test
 *
 * @package App\Tests\Integration\Entity
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $entityName ?>Test extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = <?php echo $entityName ?>::class;
}
