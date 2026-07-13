<?php
declare(strict_types = 1);

/**
 * /src/Form/Type/Traits/AddBasicFieldToForm.php
 */

namespace App\Form\Type\Traits;

use Symfony\Component\Form\FormBuilderInterface;
use function call_user_func_array;

trait AddBasicFieldToForm
{
    /**
     * @param array<int, array<int, mixed>> $fields
     */
    protected function addBasicFieldToForm(FormBuilderInterface $builder, array $fields): void
    {
        foreach ($fields as $params) {
            /** @psalm-suppress MixedArgument */
            call_user_func_array($builder->add(...), $params);
        }
    }
}
