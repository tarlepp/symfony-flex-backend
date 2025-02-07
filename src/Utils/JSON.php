<?php
declare(strict_types = 1);
/**
 * /src/Utils/JSON.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Utils;

use JsonException;
use function json_decode;
use function json_encode;

/**
 * @package App\Util
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class JSON
{
    /**
     * Generic JSON encode method with error handling support.
     *
     * @see http://php.net/manual/en/function.json-encode.php
     * @see http://php.net/manual/en/function.json-last-error.php
     *
     * @psalm-suppress FalsableReturnStatement
     * @psalm-suppress InvalidFalsableReturnType
     *
     * @param mixed $input The value being encoded. Can be any type except a resource.
     * @param int|null $options Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS,
     *                          JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT,
     *                          JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR.
     *                          The behaviour of these constants is described on the JSON constants page.
     * @param int<1, 2147483647>|null $depth Set the maximum depth. Must be greater than zero.
     *
     * @throws JsonException
     */
    public static function encode(mixed $input, ?int $options = null, ?int $depth = null): string
    {
        $options ??= 0;
        $depth ??= 512;

        return json_encode($input, JSON_THROW_ON_ERROR | $options, $depth);
    }

    /**
     * Generic JSON decode method with error handling support.
     *
     * @see http://php.net/manual/en/function.json-decode.php
     * @see http://php.net/manual/en/function.json-last-error.php
     *
     * @param string $json the json string being decoded
     * @param bool|null $assoc when TRUE, returned objects will be converted into associative arrays
     * @param int<1, 2147483647>|null $depth Set the maximum depth. Must be greater than zero.
     * @param int|null $options Bitmask of JSON decode options. Currently only JSON_BIGINT_AS_STRING is supported
     *                          (default is to cast large integers as floats)
     *
     * @throws JsonException
     */
    public static function decode(string $json, ?bool $assoc = null, ?int $depth = null, ?int $options = null): mixed
    {
        $assoc ??= false;
        $depth ??= 512;
        $options ??= 0;

        return json_decode($json, $assoc, $depth, JSON_THROW_ON_ERROR | $options);
    }
}
