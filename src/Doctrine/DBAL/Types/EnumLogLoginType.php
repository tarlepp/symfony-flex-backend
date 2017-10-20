<?php
declare(strict_types = 1);
/**
 * /src/Doctrine/DBAL/Types/EnumLogLoginType.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Doctrine\DBAL\Types;

/**
 * Class EnumLogLoginType
 *
 * @package App\Doctrine\DBAL\Types
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class EnumLogLoginType extends EnumType
{
    const TYPE_FAILURE = 'failure';
    const TYPE_SUCCESS = 'success';

    /**
     * @var string
     */
    protected static $name = 'EnumLogLogin';

    /**
     * @var array
     */
    protected static $values = [
        self::TYPE_FAILURE,
        self::TYPE_SUCCESS
    ];
}
