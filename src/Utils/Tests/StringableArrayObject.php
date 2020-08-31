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
use Stringable;

/**
 * Class StringableArrayObject
 *
 * @package App\Utils\Tests
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class StringableArrayObject extends ArrayObject implements Stringable
{
    /**
     * @throws JsonException
     */
    public function __toString(): string
    {
        /**
         * @psalm-suppress MissingClosureParamType
         * @psalm-suppress MissingClosureReturnType
         *
         * @param mixed $input
         *
         * @return mixed
         */
        $iterator = static fn ($input) => $input instanceof Stringable ? (string)$input : $input;

        return JSON::encode(array_map($iterator, $this->getArrayCopy()));
    }
}
