<?php
declare(strict_types = 1);
/**
 * /src/App/Validator/Constraints/EntityReferenceExistsValidator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Validator\Constraints;

use App\Entity\Interfaces\EntityInterface;
use App\Validator\Constraints\EntityReferenceExists as Constraint;
use Closure;
use Doctrine\ORM\EntityNotFoundException;
use Override;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraint as BaseConstraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use function array_filter;
use function array_map;
use function count;
use function implode;
use function is_array;
use function str_replace;

/**
 * Class EntityReferenceExistsValidator
 *
 * @package App\Validator\Constraints
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class EntityReferenceExistsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    #[Override]
    public function validate(mixed $value, BaseConstraint $constraint): void
    {
        if (!$constraint instanceof Constraint) {
            throw new UnexpectedTypeException($constraint, Constraint::class);
        }

        $values = $this->normalize($constraint->entityClass, $value);

        $this->check($values);
    }

    /**
     * Checks if the passed value is valid.
     *
     * @return array<array-key, EntityInterface>
     */
    private function normalize(string $target, mixed $input): array
    {
        return array_map(
            static function ($value) use ($target) {
                if (!$value instanceof $target) {
                    throw new UnexpectedValueException($value, $target);
                }

                if (!$value instanceof EntityInterface) {
                    throw new UnexpectedValueException($value, EntityInterface::class);
                }

                return $value;
            },
            is_array($input) ? $input : [$input]
        );
    }

    /**
     * @param array<array-key, EntityInterface> $entities
     */
    private function check(array $entities): void
    {
        $invalidIds = $this->getInvalidValues($entities);

        if ($invalidIds !== []) {
            $message = count($invalidIds) === 1 ? Constraint::MESSAGE_SINGLE : Constraint::MESSAGE_MULTIPLE;
            $entity = $entities[0]::class;

            $parameterEntity = str_replace('Proxies\\__CG__\\', '', $entity);
            $parameterId = count($invalidIds) > 1 ? implode('", "', $invalidIds) : $invalidIds[0];

            $this->context
                ->buildViolation($message)
                ->setParameter('{{ entity }}', $parameterEntity)
                ->setParameter('{{ id }}', $parameterId)
                ->setCode(Constraint::ENTITY_REFERENCE_EXISTS_ERROR)
                ->addViolation();
        }
    }

    /**
     * @param array<array-key, EntityInterface> $entities
     *
     * @return array<array-key, string>
     */
    private function getInvalidValues(array $entities): array
    {
        return array_map(
            static fn (EntityInterface $entity): string => $entity->getId(),
            array_filter($entities, $this->getFilterClosure())
        );
    }

    /**
     * Method to return used filter closure.
     */
    private function getFilterClosure(): Closure
    {
        return function (EntityInterface $entity): bool {
            $output = false;

            try {
                $entity->getCreatedAt();
            } catch (EntityNotFoundException $exception) {
                $this->logger->error($exception->getMessage());

                $output = true;
            }

            return $output;
        };
    }
}
