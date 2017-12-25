<?= "<?php\n" ?>
declare(strict_types=1);
/**
 * /tests/Integration/Integration/<?= $repositoryName ?>Test.php
 *
* @author  <?= $author . "\n" ?>
 */
namespace App\Tests\Integration\Repository;

use App\Entity\<?= $entityName ?>;
use App\Repository\<?= $repositoryName ?>;
use App\Resource\<?= $resourceName ?>;

/**
 * Class <?= $repositoryName ?>Test
 *
 * @package App\Tests\Integration\Repository
 * @author  <?= $author . "\n" ?>
 */
class <?= $repositoryName ?>Test extends RepositoryTestCase
{
    /**
     * @var string
     */
    protected $entityName = <?= $entityName ?>::class;

    /**
     * @var string
     */
    protected $repositoryName = <?= $repositoryName ?>::class;

    /**
     * @var string
     */
    protected $resourceName = <?= $resourceName ?>::class;

    /**
     * @var array
     */
    protected $associations = [
        'createdBy',
        'updatedBy',
    ];
}
