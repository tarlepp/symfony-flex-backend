<?php
declare(strict_types = 1);
/**
 * /src/Validator/Constraints/UniqueUsername.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Class UniqueUsername
 *
 * Usage example;
 *  App\Validator\Constraints\UniqueUsername()
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
class UniqueUsername extends Constraint
{
    public const IS_UNIQUE_USERNAME_ERROR = 'ea62740a-4d9b-4a25-9a56-46fb4c3d5fea';
    public const MESSAGE = 'This username is already taken.';

    /**
     * {@inheritdoc}
     *
     * @var array<string, string>
     */
    protected static $errorNames = [
        self::IS_UNIQUE_USERNAME_ERROR => 'IS_UNIQUE_USERNAME_ERROR',
    ];

    public function getTargets(): string
    {
        $output = null;

        if (parent::getTargets() !== self::CLASS_CONSTRAINT) {
            $output = self::CLASS_CONSTRAINT;
        }

        return $output ?? self::CLASS_CONSTRAINT;
    }
}
