<?php
declare(strict_types = 1);
/**
 * /tests/Integration/AutoMapper/TestRestRequestMapper.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Tests\Integration\AutoMapper\src;

use App\AutoMapper\RestRequestMapper;
use function str_rot13;

/**
 * Class TestRestRequestMapper
 *
 * @package App\Tests\Integration\AutoMapper\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class TestRestRequestMapper extends RestRequestMapper
{
    /**
     * Properties to map to destination object.
     */
    protected static array $properties = [
        'someProperty',
        'someTransformProperty',
    ];

    public function transformSomeTransformProperty(string $input): string
    {
        return str_rot13($input);
    }
}
