<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/LanguageValidator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Validator\Constraints;

use App\Service\Localization;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use function in_array;

/**
 * Class LanguageValidator
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LanguageValidator extends ConstraintValidator
{
    private Localization $localization;

    /**
     * LanguageValidator constructor.
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
        if (in_array($value, $this->localization->getLanguages(), true) !== true) {
            $this->context
                ->buildViolation(Language::MESSAGE)
                ->setParameter('{{ language }}', (string)$value)
                ->setCode(Language::INVALID_LANGUAGE)
                ->addViolation();
        }
    }
}
