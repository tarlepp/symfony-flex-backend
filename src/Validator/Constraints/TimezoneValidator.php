<?php
declare(strict_types = 1);

/**
 * /src/Validator/Constraints/TimezoneValidator.php
 */

namespace App\Validator\Constraints;

use App\Service\Localization;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function array_column;
use function in_array;
use function is_string;

class TimezoneValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Localization $localization,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (is_string($value)
            && !in_array($value, array_column($this->localization->getTimezones(), 'identifier'), true)
        ) {
            $this->context
                ->buildViolation(Timezone::MESSAGE)
                ->setParameter('{{ timezone }}', $value)
                ->setCode(Timezone::INVALID_TIMEZONE)
                ->addViolation();
        }
    }
}
