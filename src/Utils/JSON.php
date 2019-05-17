<?php
declare(strict_types = 1);
/**
 * /src/Utils/JSON.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Utils;

use LogicException;
use stdClass;
use const JSON_ERROR_CTRL_CHAR;
use const JSON_ERROR_DEPTH;
use const JSON_ERROR_INF_OR_NAN;
use const JSON_ERROR_NONE;
use const JSON_ERROR_RECURSION;
use const JSON_ERROR_STATE_MISMATCH;
use const JSON_ERROR_SYNTAX;
use const JSON_ERROR_UNSUPPORTED_TYPE;
use const JSON_ERROR_UTF8;
use function array_key_exists;
use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;

/**
 * Class JSON
 *
 * @package App\Util
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class JSON
{
    /**
     * @var string
     */
    private const JSON_UNKNOWN_ERROR = 'Unknown error.';

    /**
     * @var array<string>
     */
    private static $errorReference = [
        JSON_ERROR_NONE => 'No error has occurred.',
        JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded.',
        JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON.',
        JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded.',
        JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
        JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded.',
        JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded.',
        JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded.',
        JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given.',
    ];

    /**
     * Generic JSON encode method with error handling support.
     *
     * @see http://php.net/manual/en/function.json-encode.php
     * @see http://php.net/manual/en/function.json-last-error.php
     *
     * @param mixed     $input      The value being encoded. Can be any type except a resource.
     * @param integer   $options    Bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP, JSON_HEX_APOS,
     *                              JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, JSON_FORCE_OBJECT,
     *                              JSON_PRESERVE_ZERO_FRACTION, JSON_UNESCAPED_UNICODE, JSON_PARTIAL_OUTPUT_ON_ERROR.
     *                              The behaviour of these constants is described on the JSON constants page.
     * @param integer   $depth      Set the maximum depth. Must be greater than zero.
     *
     * @return string
     *
     * @throws LogicException
     */
    public static function encode($input, ?int $options = null, ?int $depth = null): string
    {
        $options = $options ?? 0;
        $depth = $depth ?? 512;

        $output = json_encode($input, $options, $depth);

        if ($output === false) {
            self::handleError();
        }

        return (string)$output;
    }

    /**
     * Generic JSON decode method with error handling support.
     *
     * @see http://php.net/manual/en/function.json-decode.php
     * @see http://php.net/manual/en/function.json-last-error.php
     *
     * @param string    $json       The json string being decoded.
     * @param boolean   $assoc      When TRUE, returned objects will be converted into associative arrays.
     * @param integer   $depth      User specified recursion depth.
     * @param integer   $options    Bitmask of JSON decode options. Currently only JSON_BIGINT_AS_STRING is supported
     *                              (default is to cast large integers as floats)
     *
     * @return stdClass|mixed|mixed[]
     *
     * @throws LogicException
     */
    public static function decode(string $json, ?bool $assoc = null, ?int $depth = null, ?int $options = null)
    {
        $assoc = $assoc ?? false;
        $depth = $depth ?? 512;
        $options = $options ?? 0;

        $output = json_decode($json, $assoc, $depth, $options);

        self::handleError();

        return $output;
    }

    /**
     * Helper method to handle possible errors within json_encode and json_decode functions.
     *
     * @throws LogicException
     */
    private static function handleError(): void
    {
        // Get last JSON error
        $error = json_last_error();

        // Oh noes, some error happened
        if ($error !== JSON_ERROR_NONE) {
            throw new LogicException(self::getErrorMessage($error) . ' - ' . json_last_error_msg());
        }
    }

    /**
     * Helper method to convert JSON error constant to human-readable-format.
     *
     * @see http://php.net/manual/en/function.json-last-error.php
     *
     * @param integer $error
     *
     * @return string
     */
    private static function getErrorMessage(int $error): string
    {
        return !array_key_exists($error, self::$errorReference) ?
            self::JSON_UNKNOWN_ERROR : self::$errorReference[$error];
    }
}
