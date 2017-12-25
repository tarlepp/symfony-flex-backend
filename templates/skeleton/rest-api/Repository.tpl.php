<?= "<?php\n" ?>
declare(strict_types = 1);
/**
 * /src/Repository/<?= $repositoryName ?>.php
 *
 * @author  <?= $author . "\n" ?>
 */
namespace App\Repository;

use App\Entity\<?= $entityName ?> as Entity;

/** @noinspection PhpHierarchyChecksInspection */
/**
 * Class <?= $repositoryName . "\n" ?>
 *
 * @package App\Repository
 * @author  <?= $author ?>
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
class <?= $repositoryName ?> extends BaseRepository
{
    /**
     * @var string
     */
    protected static $entityName = Entity::class;
}
