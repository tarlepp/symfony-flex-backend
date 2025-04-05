<?php
declare(strict_types = 1);
/**
 * /tests/Utils/StringableArrayObject.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Utils;

use App\Utils\JSON;
use ArrayObject;
use JsonException;
use Override;
use Stringable;

/**
 * @psalm-suppress MissingTemplateParam
 *
 * @package App\Tests\Utils
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class StringableArrayObject extends ArrayObject implements Stringable
{
    /**
     * @throws JsonException
     */
    #[Override]
    public function __toString(): string
    {
        $iterator = static fn (mixed $input): mixed => $input instanceof Stringable ? (string)$input : $input;

        return JSON::encode(array_map($iterator, $this->getArrayCopy()));
    }
}
