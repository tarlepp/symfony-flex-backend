<?php
declare(strict_types=1);
/**
 * /src/Rest/Traits/Resource.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Rest\Traits;

use App\Entity\EntityInterface;
use App\Rest\DTO\RestDtoInterface;

/**
 * Trait Resource
 *
 * @package App\Rest\Traits
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
trait Resource
{
    /**
     * Before lifecycle method for find method.
     *
     * @param array   $criteria
     * @param array   $orderBy
     * @param integer $limit
     * @param integer $offset
     * @param array   $search
     */
    public function beforeFind(array &$criteria, array &$orderBy, int &$limit, int &$offset, array &$search): void
    {
    }

    /**
     * After lifecycle method for find method.
     *
     * @param array             $criteria
     * @param array             $orderBy
     * @param integer           $limit
     * @param integer           $offset
     * @param array             $search
     * @param EntityInterface[] $entities
     */
    public function afterFind(
        array &$criteria,
        array &$orderBy,
        int &$limit,
        int &$offset,
        array &$search,
        array &$entities
    ): void
    {
    }

    /**
     * Before lifecycle method for findOne method.
     *
     * @param string $id
     */
    public function beforeFindOne(string &$id): void
    {
    }

    /**
     * After lifecycle method for findOne method.
     *
     * @param string               $id
     * @param null|EntityInterface $entity
     */
    public function afterFindOne(string &$id, EntityInterface $entity = null): void
    {
    }

    /**
     * Before lifecycle method for findOneBy method.
     *
     * @param array $criteria
     * @param array $orderBy
     */
    public function beforeFindOneBy(array &$criteria, array &$orderBy): void
    {
    }

    /**
     * After lifecycle method for findOneBy method.
     *
     * @param array                $criteria
     * @param array                $orderBy
     * @param null|EntityInterface $entity
     */
    public function afterFindOneBy(array &$criteria, array &$orderBy, EntityInterface $entity = null): void
    {
    }

    /**
     * Before lifecycle method for count method.
     *
     * @param array $criteria
     * @param array $search
     */
    public function beforeCount(array &$criteria, array &$search): void
    {
    }

    /**
     * Before lifecycle method for count method.
     *
     * @param array   $criteria
     * @param array   $search
     * @param integer $count
     */
    public function afterCount(array &$criteria, array &$search, int &$count): void
    {
    }

    /**
     * Before lifecycle method for create method.
     *
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function beforeCreate(RestDtoInterface $dto, EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for create method.
     *
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function afterCreate(RestDtoInterface $dto, EntityInterface $entity): void
    {
    }

    /**
     * Before lifecycle method for save method.
     *
     * @param EntityInterface $entity
     */
    public function beforeSave(EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for save method.
     *
     * @param EntityInterface $entity
     */
    public function afterSave(EntityInterface $entity): void
    {
    }

    /**
     * Before lifecycle method for update method.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function beforeUpdate(string &$id, RestDtoInterface $dto, EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for update method.
     *
     * @param string           $id
     * @param RestDtoInterface $dto
     * @param EntityInterface  $entity
     */
    public function afterUpdate(string &$id, RestDtoInterface $dto, EntityInterface $entity): void
    {
    }

    /**
     * Before lifecycle method for delete method.
     *
     * @param string          $id
     * @param EntityInterface $entity
     */
    public function beforeDelete(string &$id, EntityInterface $entity): void
    {
    }

    /**
     * After lifecycle method for delete method.
     *
     * @param string          $id
     * @param EntityInterface $entity
     */
    public function afterDelete(string &$id, EntityInterface $entity): void
    {
    }

    /**
     * Before lifecycle method for ids method.
     *
     * @param array $criteria
     * @param array $search
     */
    public function beforeIds(array &$criteria, array &$search): void
    {
    }

    /**
     * Before lifecycle method for ids method.
     *
     * @param array $criteria
     * @param array $search
     * @param array $ids
     */
    public function afterIds(array &$criteria, array &$search, array &$ids): void
    {
    }
}
