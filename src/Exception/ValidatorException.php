<?php
declare(strict_types = 1);
/**
 * /src/Exception/ValidatorException.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Exception;

use App\Exception\interfaces\ClientErrorInterface;
use App\Utils\JSON;
use JsonException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidatorException as BaseValidatorException;
use function str_replace;

/**
 * Class ValidatorException
 *
 * @package App\Exception
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class ValidatorException extends BaseValidatorException implements ClientErrorInterface
{
    /**
     * ValidatorException constructor.
     *
     * @throws JsonException
     */
    public function __construct(string $target, ConstraintViolationListInterface $errors)
    {
        $output = [];

        /** @var ConstraintViolationInterface $error */
        foreach ($errors as $error) {
            $output[] = [
                'message' => $error->getMessage(),
                'propertyPath' => $error->getPropertyPath(),
                'target' => str_replace('\\', '.', $target),
                'code' => $error->getCode(),
            ];
        }

        parent::__construct(JSON::encode($output));
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}
