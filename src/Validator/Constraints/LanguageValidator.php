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
use function is_string;

/**
 * Class LanguageValidator
 *
 * @package App\Validator\Constraints
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class LanguageValidator extends ConstraintValidator
{
    private Localization $localization;

    /**
     * LanguageValidator constructor.
     *
     * @param Localization $localization
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
            if (!is_string($value)) {
                $value = $value->getLanguage();
            }

            $this->context
                ->buildViolation(Language::MESSAGE)
                ->setParameter('{{ language }}', $value)
                ->setCode(Language::INVALID_LANGUAGE)
                ->addViolation();
        }
    }
}
