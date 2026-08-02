<?php
declare(strict_types = 1);

/**
 * /src/Validator/Constraints/LanguageValidator.php
 */

namespace App\Validator\Constraints;

use App\Service\Localization;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function in_array;

class LanguageValidator extends ConstraintValidator
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
        if (in_array($value, $this->localization->getLanguages(), true) !== true) {
            $this->context
                ->buildViolation(Language::MESSAGE)
                ->setParameter('{{ language }}', (string)$value)
                ->setCode(Language::INVALID_LANGUAGE)
                ->addViolation();
        }
    }
}
