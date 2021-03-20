<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueEmail.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueEmail
 *
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
    public const IS_UNIQUE_EMAIL_ERROR = 'd487278d-8b13-4da0-b4cc-f862e6e99af6';
    public const MESSAGE = 'This email is already taken.';

    /**
     * {@inheritdoc}
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::IS_UNIQUE_EMAIL_ERROR => 'IS_UNIQUE_EMAIL_ERROR',
    ];

    /**
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
