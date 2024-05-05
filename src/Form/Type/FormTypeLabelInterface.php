<?php
declare(strict_types = 1);
/**
 * /src/Form/Type/FormTypeLabelInterface.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Form\Type;

/**
 * Interface FormTypeLabelInterface
 *
 * @package App\Form\Type
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
interface FormTypeLabelInterface
{
    // @codeCoverageIgnoreStart
    public const string LABEL = 'label';
    public const string REQUIRED = 'required';
    public const string EMPTY_DATA = 'empty_data';
    public const string TYPE = 'type';
    public const string FIRST_NAME = 'first_name';
    public const string FIRST_OPTIONS = 'first_options';
    public const string SECOND_NAME = 'second_name';
    public const string SECOND_OPTIONS = 'second_options';
    public const string CHOICES = 'choices';
    public const string CHOICE_LABEL = 'choice_label';
    public const string CLASS_NAME = 'class';
    // @codeCoverageIgnoreEnd
}
