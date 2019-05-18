<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/<?php echo $resourceName ?>Test.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\<?php echo $entityName ?>;
use App\Repository\<?php echo $repositoryName ?>;
use App\Resource\<?php echo $resourceName ?>;

/**
 * Class <?php echo $resourceName ?>Test
 *
 * @package App\Tests\Integration\Resource
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $resourceName ?>Test extends ResourceTestCase
{
    protected $entityClass = <?php echo $entityName ?>::class;
    protected $resourceClass = <?php echo $resourceName ?>::class;
    protected $repositoryClass = <?php echo $repositoryName ?>::class;
}
