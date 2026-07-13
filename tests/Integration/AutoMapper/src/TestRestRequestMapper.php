<?php
declare(strict_types = 1);

/**
 * /tests/Integration/AutoMapper/TestRestRequestMapper.php
 */

namespace App\Tests\Integration\AutoMapper\src;

use App\AutoMapper\RestRequestMapper;
use function str_rot13;

final class TestRestRequestMapper extends RestRequestMapper
{
    /**
     * @var array<int, non-empty-string>
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
