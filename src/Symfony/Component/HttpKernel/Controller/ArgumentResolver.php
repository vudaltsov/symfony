<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpKernel\Controller;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestAttributeValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\SessionValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\VariadicValueResolver;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactoryInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\Config\ArgumentConfigInterface;

/**
 * Responsible for resolving the arguments passed to an action.
 *
 * @author Iltar van der Berg <kjarli@gmail.com>
 */
final class ArgumentResolver implements ArgumentResolverInterface
{
    private $argumentMetadataFactory;

    /**
     * @var iterable|ArgumentValueResolverInterface[]
     */
    private $argumentValueResolvers;

    private $configuredArgumentValueResolvers;

    public function __construct(ArgumentMetadataFactoryInterface $argumentMetadataFactory = null, iterable $argumentValueResolvers = [], ContainerInterface $configuredArgumentValueResolvers = null)
    {
        $this->argumentMetadataFactory = $argumentMetadataFactory ?? new ArgumentMetadataFactory();
        $this->argumentValueResolvers = $argumentValueResolvers ?: self::getDefaultArgumentValueResolvers();
        $this->configuredArgumentValueResolvers = $configuredArgumentValueResolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(Request $request, $controller)
    {
        $arguments = [];

        foreach ($this->argumentMetadataFactory->createArgumentMetadata($controller) as $metadata) {
            if (null !== $config = $metadata->getConfig()) {
                $values = $this->resolveConfigured($request, $metadata, $config);
            } else {
                $values = $this->resolveNonConfigured($request, $metadata, $controller);
            }

            foreach ($values as $value) {
                $arguments[] = $value;
            }
        }

        return $arguments;
    }

    public static function getDefaultArgumentValueResolvers(): iterable
    {
        return [
            new RequestAttributeValueResolver(),
            new RequestValueResolver(),
            new SessionValueResolver(),
            new DefaultValueResolver(),
            new VariadicValueResolver(),
        ];
    }

    private function resolveConfigured(Request $request, ArgumentMetadata $metadata, ArgumentConfigInterface $config): \Generator
    {
        $class = $config::resolvedBy();

        if (null === $this->configuredArgumentValueResolvers || !$this->configuredArgumentValueResolvers->has($class)) {
            throw new \RuntimeException(sprintf('Argument value resolver "%s" does not exist or is not enabled. Check the "resolvedBy" method in your config class "%s".', $class, \get_class($config)));
        }

        $resolver = $this->configuredArgumentValueResolvers->get($class);

        if (!$resolver instanceof ConfiguredArgumentValueResolverInterface) {
            throw new \UnexpectedValueException(sprintf('Argument value resolver "%s" must implement "%s".', \get_class($resolver), ConfiguredArgumentValueResolverInterface::class));
        }

        return $resolver->resolveConfigured($request, $metadata, $config);
    }

    private function resolveNonConfigured(Request $request, ArgumentMetadata $metadata, $controller): \Generator
    {
        foreach ($this->argumentValueResolvers as $resolver) {
            if ($resolver->supports($request, $metadata)) {
                $values = $resolver->resolve($request, $metadata);

                if (!$values instanceof \Generator) {
                    throw new \UnexpectedValueException(sprintf('%s::resolve() must yield at least one value.', \get_class($resolver)));
                }

                return $values;
            }
        }

        throw new \RuntimeException(sprintf('Controller "%s" requires that you provide a value for the "$%s" argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.', $this->getControllerName($controller), $metadata->getName()));
    }

    private function getControllerName($controller): string
    {
        if (\is_array($controller)) {
            return sprintf('%s::%s()', \get_class($controller[0]), $controller[1]);
        }

        if (\is_object($controller)) {
            return \get_class($controller);
        }

        return $controller;
    }
}
