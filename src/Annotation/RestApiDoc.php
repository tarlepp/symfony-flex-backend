<?php
declare(strict_types = 1);
/**
 * /src/Annotation/RestApiDoc.php
 *
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */

namespace App\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * Class RestApiDoc
 *
 * @Annotation
 * @Annotation\Target({"CLASS", "METHOD"})
 *
 * @package App\Annotation
 * @author  TLe, Tarmo Leppänen <tarmo.leppanen@protacon.com>
 */
class RestApiDoc
{
    /**
     * @var bool
     */
    public $disabled = false;
}
