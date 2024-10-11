<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueEmail.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

/**
 * Usage example;
 *  App\Validator\Constraints\UniqueEmail()
 *
 * Just add that to your class as an annotation and you're good to go.
 *
 * @Annotation
 * @Target({"CLASS"})
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class UniqueEmail extends Constraint
{
    final public const string IS_UNIQUE_EMAIL_ERROR = 'd487278d-8b13-4da0-b4cc-f862e6e99af6';
    final public const string MESSAGE = 'This email is already taken.';

    /**
     * {@inheritdoc}
     *
     * @psalm-var array<string, string>
     */
    protected const array ERROR_NAMES = [
        self::IS_UNIQUE_EMAIL_ERROR => 'IS_UNIQUE_EMAIL_ERROR',
    ];

    #[Override]
    public function getTargets(): string
    {
        $output = null;

        if (parent::getTargets() !== self::CLASS_CONSTRAINT) {
            $output = self::CLASS_CONSTRAINT;
        }

        return $output ?? self::CLASS_CONSTRAINT;
    }
}
