<?php
declare(strict_types = 1);
/**
 * /src/Rest/RequestHandler.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Rest;

use App\Utils\JSON;
use Closure;
use JsonException;
use LogicException;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use function abs;
use function array_filter;
use function array_key_exists;
use function array_unique;
use function array_values;
use function array_walk;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function mb_strtoupper;
use function mb_substr;
use function strncmp;

/**
 * Class RequestHandler
 *
 * @package App\Rest
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
final class RequestHandler
{
    /**
     * Method to get used criteria array for 'find' and 'count' methods. Some
     * examples below.
     *
     * Basic usage:
     *  ?where={"foo": "bar"} => WHERE entity.foo = 'bar'
     *  ?where={"bar.foo": "foobar"} => WHERE bar.foo = 'foobar'
     *  ?where={"id": [1,2,3]} => WHERE entity.id IN (1,2,3)
     *  ?where={"bar.foo": [1,2,3]} => WHERE bar.foo IN (1,2,3)
     *
     * Advanced usage:
     *  By default you cannot make anything else that described above,
     *  but you can easily manage special cases within your controller
     *  'processCriteria' method, where you can modify this generated
     *  'criteria' array as you like.
     *
     *  Note that with advanced usage you can easily use everything that
     *  App\Repository\Base::getExpression method supports - and that is
     *  basically 99% that you need on advanced search criteria.
     *
     * @return array<string, string|array<string|int, string|int>>
     *
     * @throws HttpException
     */
    public static function getCriteria(HttpFoundationRequest $request): array
    {
        try {
            $where = array_filter(
                (array)JSON::decode((string)$request->get('where', '{}'), true),
                static fn ($value): bool => $value !== null
            );
        } catch (JsonException $error) {
            throw new HttpException(
                HttpFoundationResponse::HTTP_BAD_REQUEST,
                'Current \'where\' parameter is not valid JSON.',
                $error
            );
        }

        return $where;
    }

    /**
     * Getter method for used order by option within 'find' method. Some
     * examples below.
     *
     * Basic usage:
     *  ?order=column1 => ORDER BY entity.column1 ASC
     *  ?order=-column1 => ORDER BY entity.column2 DESC
     *  ?order=foo.column1 => ORDER BY foo.column1 ASC
     *  ?order=-foo.column1 => ORDER BY foo.column2 DESC
     *
     * Array parameter usage:
     *  ?order[column1]=ASC => ORDER BY entity.column1 ASC
     *  ?order[column1]=DESC => ORDER BY entity.column1 DESC
     *  ?order[column1]=foobar => ORDER BY entity.column1 ASC
     *  ?order[column1]=DESC&order[column2]=DESC => ORDER BY entity.column1 DESC, entity.column2 DESC
     *  ?order[foo.column1]=ASC => ORDER BY foo.column1 ASC
     *  ?order[foo.column1]=DESC => ORDER BY foo.column1 DESC
     *  ?order[foo.column1]=foobar => ORDER BY foo.column1 ASC
     *  ?order[foo.column1]=DESC&order[column2]=DESC => ORDER BY foo.column1 DESC, entity.column2 DESC
     *
     * @return array<string, string>
     */
    public static function getOrderBy(HttpFoundationRequest $request): array
    {
        // Normalize parameter value
        $input = array_filter((array)$request->get('order', []));

        // Initialize output
        $output = [];

        // Process user input
        array_walk($input, self::getIterator($output));

        return $output;
    }

    /**
     * Getter method for used limit option within 'find' method.
     *
     * Usage:
     *  ?limit=10
     */
    public static function getLimit(HttpFoundationRequest $request): ?int
    {
        $limit = $request->get('limit');

        return $limit !== null ? (int)abs($limit) : null;
    }

    /**
     * Getter method for used offset option within 'find' method.
     *
     * Usage:
     *  ?offset=10
     */
    public static function getOffset(HttpFoundationRequest $request): ?int
    {
        $offset = $request->get('offset');

        return $offset !== null ? (int)abs($offset) : null;
    }

    /**
     * Getter method for used search terms within 'find' and 'count' methods.
     * Note that these will affect to columns / properties that you have
     * specified to your resource service repository class.
     *
     * Usage examples:
     *  ?search=term
     *  ?search=term1+term2
     *  ?search={"and": ["term1", "term2"]}
     *  ?search={"or": ["term1", "term2"]}
     *  ?search={"and": ["term1", "term2"], "or": ["term3", "term4"]}
     *
     * @return array<mixed>
     *
     * @throws HttpException
     */
    public static function getSearchTerms(HttpFoundationRequest $request): array
    {
        $search = $request->get('search');

        return $search !== null ? self::getSearchTermCriteria($search) : [];
    }

    /**
     * Method to return search term criteria as an array that repositories can easily use.
     *
     * @return array<string|int, array<int, string>|string>
     *
     * @throws HttpException
     */
    private static function getSearchTermCriteria(string $search): array
    {
        $searchTerms = self::determineSearchTerms($search);

        // By default we want to use 'OR' operand with given search words.
        $output = [
            'or' => array_unique(array_values(array_filter(explode(' ', $search)))),
        ];

        if ($searchTerms !== null) {
            $output = self::normalizeSearchTerms($searchTerms);
        }

        return $output;
    }

    /**
     * Method to determine used search terms. Note that this will first try to JSON decode given search term. This is
     * for cases that 'search' request parameter contains 'and' or 'or' terms.
     *
     * @return array<int, array<int, string>|string>|null
     *
     * @throws HttpException
     */
    private static function determineSearchTerms(string $search): ?array
    {
        try {
            $searchTerms = JSON::decode($search, true);

            self::checkSearchTerms($searchTerms);
        } catch (JsonException | LogicException $exception) {
            (static fn (Throwable $exception): string => (string)$exception)($exception);

            $searchTerms = null;
        }

        return $searchTerms;
    }

    /**
     * @param mixed $searchTerms
     *
     * @throws LogicException
     * @throws HttpException
     */
    private static function checkSearchTerms($searchTerms): void
    {
        if (!is_array($searchTerms)) {
            throw new LogicException('Search term is not an array, fallback to string handling');
        }

        if (!array_key_exists('and', $searchTerms) && !array_key_exists('or', $searchTerms)) {
            throw new HttpException(
                HttpFoundationResponse::HTTP_BAD_REQUEST,
                'Given search parameter is not valid, within JSON provide \'and\' and/or \'or\' property.'
            );
        }
    }

    /**
     * Method to normalize specified search terms. Within this we will just filter out any "empty" values and return
     * unique terms after that.
     *
     * @param array<int, array<int, string>|string> $searchTerms
     *
     * @return array<int, array<int, string>|string>
     */
    private static function normalizeSearchTerms(array $searchTerms): array
    {
        // Normalize user input, note that this support array and string formats on value
        array_walk($searchTerms, static fn (array $terms): array => array_unique(array_values(array_filter($terms))));

        return $searchTerms;
    }

    /**
     * @param array<string, string> $output
     *
     * @psalm-suppress MissingClosureParamType
     */
    private static function getIterator(array &$output): Closure
    {
        return static function (string $value, $key) use (&$output): void {
            $order = in_array(mb_strtoupper($value), ['ASC', 'DESC'], true) ? mb_strtoupper($value) : 'ASC';
            $column = is_string($key) ? $key : $value;

            if (strncmp($column, '-', 1) === 0) {
                $column = mb_substr($column, 1);
                $order = 'DESC';
            }

            $output[$column] = $order;
        };
    }
}
