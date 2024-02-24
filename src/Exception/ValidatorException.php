<?php
declare(strict_types = 1);
/**
 * /src/Exception/ValidatorException.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Exception;

use App\Exception\interfaces\ClientErrorInterface;
use App\Exception\models\ValidatorError;
use App\Utils\JSON;
use JsonException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException as BaseValidatorException;
use function array_map;
use function iterator_to_array;

/**
 * @package App\Exception
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ValidatorException extends BaseValidatorException implements ClientErrorInterface
{
    /**
     * @throws JsonException
     */
    public function __construct(string $target, ConstraintViolationListInterface $errors)
    {
        parent::__construct(
            JSON::encode(
                array_map(
                    static fn (ConstraintViolationInterface $error): ValidatorError =>
                        new ValidatorError($error, $target),
                    iterator_to_array($errors),
                ),
            ),
        );
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}
