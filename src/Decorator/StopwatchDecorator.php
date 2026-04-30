<?php
declare(strict_types = 1);
/**
 * /src/Decorator/StopwatchDecorator.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Decorator;

use App\Entity\Interfaces\EntityInterface;
use Closure;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;
use function array_filter;
use function implode;
use function is_object;
use function str_contains;
use function str_replace;
use function uniqid;
use function var_export;

/**
 * @package App\Decorator
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
readonly class StopwatchDecorator
{
    public function __construct(
        private Stopwatch $stopwatch,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Decorates a service with stopwatch timing interceptors.
     *
     * @template T of object
     *
     * @param T $service
     *
     * @return T
     */
    public function decorate(object $service): object
    {
        $reflection = new ReflectionClass($service);

        if ($this->shouldSkipDecoration($reflection)) {
            return $service;
        }

        [$prefixInterceptors, $suffixInterceptors] = $this->getPrefixAndSuffixInterceptors($reflection);

        /** @var T */
        return $this->createProxy($service, $reflection, $prefixInterceptors, $suffixInterceptors) ?? $service;
    }

    // Validation methods

    private function shouldSkipDecoration(ReflectionClass $class): bool
    {
        return $class->getFileName() === false
            || $class->isFinal()
            || $this->isExcludedClassName($class->getName());
    }

    private function isExcludedClassName(string $className): bool
    {
        return str_contains($className, 'RequestStack')
            || str_contains($className, 'Mock_')
            || str_contains($className, 'StopwatchProxy_');
    }

    // Interceptor creation methods

    /**
     * @return array{0: array<string, Closure>, 1: array<string, Closure>}
     */
    private function getPrefixAndSuffixInterceptors(ReflectionClass $class): array
    {
        $className = $class->getName();

        $prefixInterceptors = [];
        $suffixInterceptors = [];

        $methods = $this->getProxyableMethods($class);

        $stopwatch = $this->stopwatch;
        $decorator = $this;

        foreach ($methods as $method) {
            $methodName = $method->getName();
            $eventName = "{$class->getShortName()}->{$methodName}";

            $prefixInterceptors[$methodName] = $this->createPrefixInterceptor($eventName, $className, $stopwatch);
            $suffixInterceptors[$methodName] = $this->createSuffixInterceptor($eventName, $stopwatch, $decorator);
        }

        return [$prefixInterceptors, $suffixInterceptors];
    }

    private function createPrefixInterceptor(string $eventName, string $className, Stopwatch $stopwatch): Closure
    {
        return static function () use ($eventName, $className, $stopwatch): void {
            $stopwatch->start($eventName, $className);
        };
    }

    private function createSuffixInterceptor(
        string $eventName,
        Stopwatch $stopwatch,
        self $decorator,
    ): Closure {
        return static function (
            mixed $proxy,
            mixed $instance,
            mixed $method,
            mixed $params,
            mixed &$returnValue,
        ) use (
            $eventName,
            $stopwatch,
            $decorator,
        ): void {
            $stopwatch->stop($eventName);

            // Don't decorate if we can't determine safety of decoration
            if ($instance !== null) {
                $decorator->decorateReturnValueSafely($returnValue, $instance);
            } else {
                $decorator->decorateReturnValue($returnValue); // @codeCoverageIgnore
            }
        };
    }

    private function decorateReturnValue(mixed &$returnValue): void
    {
        if (is_object($returnValue)
            && !$returnValue instanceof EntityInterface
            && !$this->isAlreadyProxied($returnValue)
        ) {
            $returnValue = $this->decorate($returnValue);
        }
    }

    private function decorateReturnValueSafely(mixed &$returnValue, object $wrappedInstance): void
    {
        // Don't decorate if the return value is the wrapped instance itself (fluent interface)
        if ($returnValue === $wrappedInstance) {
            return;
        }

        $this->decorateReturnValue($returnValue);
    }

    private function isAlreadyProxied(object $obj): bool
    {
        return str_contains($obj::class, 'StopwatchProxy_');
    }

    // Proxy creation methods

    /**
     * @param array<string, Closure> $prefixInterceptors
     * @param array<string, Closure> $suffixInterceptors
     */
    private function createProxy(
        object $service,
        ReflectionClass $reflection,
        array $prefixInterceptors,
        array $suffixInterceptors,
    ): ?object {
        $className = $reflection->getName();
        $uniqueId = str_replace('.', '_', uniqid('', true));
        $proxyClassName = 'StopwatchProxy_' . str_replace('\\', '_', $className) . '_' . $uniqueId;

        try {
            $classCode = $this->generateProxyClass(
                $proxyClassName,
                $className,
                $reflection,
            );

            // phpcs:ignore Squiz.PHP.Eval
            eval($classCode);

            /** @psalm-suppress InvalidStringClass */
            return new $proxyClassName($service, $prefixInterceptors, $suffixInterceptors);
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            $this->logger->error(
                'StopwatchDecorator: Failed to create proxy for {class}: {message}',
                [
                    'class' => $service::class,
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ],
            );

            return null;
        }
        // @codeCoverageIgnoreEnd
    }

    // Proxy class generation methods

    private function generateProxyClass(
        string $proxyClassName,
        string $originalClassName,
        ReflectionClass $reflection,
    ): string {
        $methods = $this->getProxyableMethods($reflection);
        $methodsCode = $this->generateProxyMethods($methods);

        // phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
        return <<<CODE

class $proxyClassName extends $originalClassName {
    private object \$wrappedInstance;
    private array \$prefixInterceptors;
    private array \$suffixInterceptors;

    public function __construct(object \$wrappedInstance, array \$prefixInterceptors, array \$suffixInterceptors) {
        \$this->wrappedInstance = \$wrappedInstance;
        \$this->prefixInterceptors = \$prefixInterceptors;
        \$this->suffixInterceptors = \$suffixInterceptors;
    }
$methodsCode
}
CODE;
        // phpcs:enable PSR1.Classes.ClassDeclaration.MultipleClasses
    }

    /**
     * @param array<ReflectionMethod> $methods
     */
    private function generateProxyMethods(array $methods): string
    {
        $methodsCode = '';

        foreach ($methods as $method) {
            $methodsCode .= $this->generateProxyMethod($method);
        }

        return $methodsCode;
    }

    private function generateProxyMethod(ReflectionMethod $method): string
    {
        $methodName = $method->getName();
        [$paramsList, $argsList] = $this->buildMethodParameters($method);
        [$returnType, $isVoid] = $this->getMethodReturnType($method);
        $methodBody = $this->generateMethodBody($methodName, $argsList, $isVoid);

        return <<<CODE

    public function $methodName($paramsList)$returnType {
        if (isset(\$this->prefixInterceptors['$methodName'])) {
            (\$this->prefixInterceptors['$methodName'])();
        }
$methodBody    }

CODE;
    }

    private function generateMethodBody(string $methodName, string $argsList, bool $isVoid): string
    {
        return $isVoid
            ? $this->generateVoidMethodBody($methodName, $argsList)
            : $this->generateNonVoidMethodBody($methodName, $argsList);
    }

    private function generateVoidMethodBody(string $methodName, string $argsList): string
    {
        return <<<CODE
        \$this->wrappedInstance->$methodName($argsList);

        if (isset(\$this->suffixInterceptors['$methodName'])) {
            \$returnValue = null;
            (\$this->suffixInterceptors['$methodName'])(
                null, \$this->wrappedInstance, null, func_get_args(), \$returnValue
            );
        }

CODE;
    }

    private function generateNonVoidMethodBody(string $methodName, string $argsList): string
    {
        return <<<CODE
        \$returnValue = \$this->wrappedInstance->$methodName($argsList);

        if (isset(\$this->suffixInterceptors['$methodName'])) {
            (\$this->suffixInterceptors['$methodName'])(
                null, \$this->wrappedInstance, null, func_get_args(), \$returnValue
            );
        }

        // Handle fluent interfaces: if the wrapped instance returns itself, return the proxy
        if (\$returnValue === \$this->wrappedInstance) {
            return \$this;
        }

        return \$returnValue;

CODE;
    }

    // Method parameter handling

    /**
     * @return array{0: string, 1: string}
     */
    private function buildMethodParameters(ReflectionMethod $method): array
    {
        $params = [];
        $args = [];

        foreach ($method->getParameters() as $param) {
            $params[] = $this->buildParameterSignature($param);
            $argsList = ($param->isVariadic() ? '...' : '') . '$' . $param->getName();
            $args[] = $argsList;
        }

        return [implode(', ', $params), implode(', ', $args)];
    }

    private function buildParameterSignature(\ReflectionParameter $param): string
    {
        $paramStr = $this->getParameterTypeHint($param);
        $paramStr .= $this->getParameterModifiers($param);
        $paramStr .= '$' . $param->getName();
        $paramStr .= $this->getParameterDefaultValue($param);

        return $paramStr;
    }

    private function getParameterTypeHint(\ReflectionParameter $param): string
    {
        return $param->hasType() ? (string)$param->getType() . ' ' : '';
    }

    private function getParameterModifiers(\ReflectionParameter $param): string
    {
        $reference = $param->isPassedByReference() ? '&' : '';
        $variadic = $param->isVariadic() ? '...' : '';

        return $reference . $variadic;
    }

    private function getParameterDefaultValue(\ReflectionParameter $param): string
    {
        return $param->isOptional() && !$param->isVariadic()
            ? $this->getDefaultValueString($param)
            : '';
    }

    private function getDefaultValueString(\ReflectionParameter $param): string
    {
        $result = '';

        if ($param->isDefaultValueAvailable()) {
            /** @psalm-suppress MixedAssignment */
            $defaultValue = $param->getDefaultValue();
            $result = ' = ' . var_export($defaultValue, true);
        }

        return $result;
    }

    // Return type handling

    /**
     * @return array{0: string, 1: bool}
     */
    private function getMethodReturnType(ReflectionMethod $method): array
    {
        $returnType = '';
        $isVoid = false;

        if ($method->hasReturnType()) {
            $type = $method->getReturnType();
            $typeString = (string)$type;
            $returnType = ': ' . $typeString;
            // `never` methods throw or exit and never return normally; treat as void so the
            // proxy body contains no `return` statement (which would be a PHP fatal error).
            $isVoid = $typeString === 'void' || $typeString === 'never';
        }

        return [$returnType, $isVoid];
    }

    // Method filtering

    /**
     * @return array<ReflectionMethod>
     */
    private function getProxyableMethods(ReflectionClass $class): array
    {
        return array_filter(
            $class->getMethods(ReflectionMethod::IS_PUBLIC),
            fn (ReflectionMethod $method): bool => $this->isProxyableMethod($method)
        );
    }

    private function isProxyableMethod(ReflectionMethod $method): bool
    {
        return !$method->isStatic()
            && !$method->isFinal()
            && !$method->isConstructor()
            && !$method->isDestructor();
    }
}
