<?= "<?php\n" ?>
declare(strict_types = 1);
/**
 * /tests/Integration/Resource/<?= $resourceName ?>Test.php
 *
 * @author  <?= $author . "\n" ?>
 */
namespace App\Tests\Integration\Resource;

use App\Entity\<?= $entityName ?>;
use App\Repository\<?= $repositoryName ?>;
use App\Resource\<?= $resourceName ?>;

/**
 * Class <?= $resourceName ?>Test
 *
 * @package App\Tests\Integration\Resource
 * @author  <?= $author . "\n" ?>
 */
class <?= $resourceName ?>Test extends ResourceTestCase
{
    protected $entityClass = <?= $entityName ?>::class;
    protected $resourceClass = <?= $resourceName ?>::class;
    protected $repositoryClass = <?= $repositoryName ?>::class;
}
