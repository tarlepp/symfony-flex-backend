<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Decorator/FluentService.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Decorator;

/**
 * Helper class for testing fluent interface (methods that return $this) decoration.
 *
 * @psalm-suppress ClassMustBeFinal
 */
class FluentService
{
    private string $value = '';

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
