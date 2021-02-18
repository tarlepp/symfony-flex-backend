<?php
declare(strict_types = 1);
/**
 * /src/Exception/models/ValidatorError.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Exception\models;

use Stringable;
use Symfony\Component\Validator\ConstraintViolationInterface;
use function str_replace;

/**
 * Class ValidatorError
 *
 * @package App\Exception\models
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ValidatorError
{
    public string | Stringable $message;
    public string $propertyPath;
    public string $target;
    public string | null $code;

    public function __construct(ConstraintViolationInterface $error, string $target)
    {
        $this->message = $error->getMessage();
        $this->propertyPath = $error->getPropertyPath();
        $this->target = str_replace('\\', '.', $target);
        $this->code = $error->getCode();
    }
}
