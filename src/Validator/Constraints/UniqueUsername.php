<?php
declare(strict_types = 1);

/**
 * /src/Validator/Constraints/UniqueUsername.php
 */

namespace App\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

/**
 * Usage example;
 *  App\Validator\Constraints\UniqueUsername()
 *
 * Just add that to your class as an annotation and you're good to go.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class UniqueUsername extends Constraint
{
    final public const string IS_UNIQUE_USERNAME_ERROR = 'ea62740a-4d9b-4a25-9a56-46fb4c3d5fea';
    final public const string MESSAGE = 'This username is already taken.';

    /**
     * {@inheritDoc}
     *
     * @psalm-var array<string, string>
     */
    protected const array ERROR_NAMES = [
        self::IS_UNIQUE_USERNAME_ERROR => 'IS_UNIQUE_USERNAME_ERROR',
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
