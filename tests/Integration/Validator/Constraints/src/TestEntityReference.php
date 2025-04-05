<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Validator/Constraints/src/TestEntityReference.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Validator\Constraints\src;

use App\Entity\Interfaces\EntityInterface;
use DateTimeImmutable;
use Doctrine\ORM\EntityNotFoundException;
use Override;

/**
 * @package App\Tests\Integration\Validator\Constraints\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
final class TestEntityReference implements EntityInterface
{
    public function __construct(
        private readonly bool $throwException = false
    ) {
    }

    #[Override]
    public function getId(): string
    {
        return 'xxx';
    }

    #[Override]
    public function getCreatedAt(): ?DateTimeImmutable
    {
        if ($this->throwException) {
            throw new EntityNotFoundException('Entity not found');
        }

        return null;
    }
}
