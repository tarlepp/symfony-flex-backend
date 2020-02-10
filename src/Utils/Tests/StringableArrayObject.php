<?php
declare(strict_types = 1);
/**
 * /src/Utils/Tests/StringableArrayObject.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
namespace App\Utils\Tests;

use App\Utils\JSON;
use ArrayObject;
use JsonException;
use function is_object;

/**
 * Class StringableArrayObject
 *
 * @package App\Utils\Tests
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class StringableArrayObject extends ArrayObject
{
    /**
     * @return string
     *
     * @throws JsonException
     */
    public function __toString(): string
    {
        $iterator = fn ($input) => is_object($input) ? (string)$input : $input;

        return JSON::encode(array_map($iterator, $this->getArrayCopy()));
    }
}
