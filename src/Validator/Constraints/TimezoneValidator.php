<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/TimezoneValidator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use App\Service\Localization;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function array_column;
use function in_array;
use function is_string;

/**
 * Class TimezoneValidator
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class TimezoneValidator extends ConstraintValidator
{
    private Localization $localization;

    /**
     * TimezoneValidator constructor.
     */
    public function __construct(Localization $localization)
    {
        $this->localization = $localization;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (in_array($value, array_column($this->localization->getTimezones(), 'identifier'), true) !== true) {
            if (!is_string($value)) {
                $value = $value->getTimezone();
            }

            $this->context
                ->buildViolation(Timezone::MESSAGE)
                ->setParameter('{{ timezone }}', $value)
                ->setCode(Timezone::INVALID_TIMEZONE)
                ->addViolation();
        }
    }
}
