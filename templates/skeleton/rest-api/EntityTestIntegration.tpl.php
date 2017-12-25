<?= "<?php\n" ?>
declare(strict_types=1);
/**
 * /tests/Integration/Entity//<?= $entityName ?>Test.php
 *
 * @author  <?= $author . "\n" ?>
 */
namespace App\Tests\Integration\Entity;

use App\Entity\<?= $entityName ?>;

/**
 * Class <?= $entityName ?>Test
 *
 * @package App\Tests\Integration\Entity
 * @author  <?= $author . "\n" ?>
 */
class <?= $entityName ?>Test extends EntityTestCase
{
    /**
     * @var string
     */
    protected $entityName = <?= $entityName ?>::class;
}
