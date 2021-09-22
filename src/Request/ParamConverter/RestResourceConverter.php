<?php
declare(strict_types = 1);
/**
 * /src/Request/ParamConverter/RestResourceConverter.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Request\ParamConverter;

use App\Resource\ResourceCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

/**
 * Class RestResourceConverter
 *
 * Purpose of this param converter is to use exactly same methods and workflow
 * as in basic REST API requests.
 *
 * @package App\Request\ParamConverter
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RestResourceConverter implements ParamConverterInterface
{
    public function __construct(
        private ResourceCollection $collection,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws Throwable
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $name = $configuration->getName();
        $identifier = (string)$request->attributes->get($name, '');
        $resource = $this->collection->get((string)$configuration->getClass());

        if ($identifier !== '') {
            // Reminder make throw to exist on options
            $request->attributes->set($name, $resource->findOne($identifier, true));
        }

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $this->collection->has($configuration->getClass());
    }
}
