<?php echo "<?php\n" ?>
declare(strict_types = 1);
/**
 * /src/Repository/<?php echo $repositoryName ?>.php
 *
 * @author  <?php echo $author . "\n" ?>
 */
namespace App\Repository;

use App\Entity\<?php echo $entityName ?> as Entity;

/** @noinspection PhpHierarchyChecksInspection */
/**
 * Class <?php echo $repositoryName . "\n" ?>
 *
 * @package App\Repository
 * @author  <?php echo $author ?>
 *
 * @codingStandardsIgnoreStart
 *
 * @method Entity|null find(string $id, string $lockMode = null, string $lockVersion = null)
 * @method Entity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entity[]    findBy(array $criteria, array $orderBy = null, int $limit = null, int $offset = null)
 * @method Entity[]    findByAdvanced(array $criteria, array $orderBy = null, int $limit = null, int $offset = null, array $search = null): array
 * @method Entity[]    findAll()
 *
 * @codingStandardsIgnoreEnd
 */
class <?php echo $repositoryName ?> extends BaseRepository
{
    /**
     * @var string
     */
    protected static $entityName = Entity::class;
}
