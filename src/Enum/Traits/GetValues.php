<?php
declare(strict_types = 1);

/**
 * /src/Enum/Traits/GetValues.php
 */

namespace App\Enum\Traits;

use function array_column;

trait GetValues
{
    /**
     * @return array<int, string>
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
