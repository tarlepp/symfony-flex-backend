<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Integration/Integration/<?php echo $repositoryName ?>Test.php
 *
* @author  <?php echo $author . "\n" ?>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\<?php echo $entityName ?>;
use App\Repository\<?php echo $repositoryName ?>;
use App\Resource\<?php echo $resourceName ?>;

/**
 * Class <?php echo $repositoryName ?>Test
 *
 * @package App\Tests\Integration\Repository
 * @author  <?php echo $author . "\n" ?>
 */
class <?php echo $repositoryName ?>Test extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = <?php echo $entityName ?>::class;

    /**
     * @var string
     */
    protected $repositoryName = <?php echo $repositoryName ?>::class;

    /**
     * @var string
     */
    protected $resourceName = <?php echo $resourceName ?>::class;

    /**
     * @var array
     */
    protected $associations = [
        'createdBy',
        'updatedBy',
    ];
}
