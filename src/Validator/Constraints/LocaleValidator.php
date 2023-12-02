<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/LocaleValidator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use App\Service\Localization;
use Override;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function in_array;

/**
 * Class LocaleValidator
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class LocaleValidator extends ConstraintValidator
{
    public function __construct(
        private readonly Localization $localization,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (in_array($value, $this->localization->getLocales(), true) !== true) {
            $this->context
                ->buildViolation(Locale::MESSAGE)
                ->setParameter('{{ locale }}', (string)$value)
                ->setCode(Locale::INVALID_LOCALE)
                ->addViolation();
        }
    }
}
