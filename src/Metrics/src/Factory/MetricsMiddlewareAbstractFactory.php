<?php

namespace rollun\metrics\Factory;

use Interop\Container\ContainerInterface;
use rollun\metrics\MetricsProviderInterface;
use rollun\metrics\MetricsMiddleware;
use rollun\utils\Factory\AbstractAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class MetricsMiddlewareAbstractFactory extends AbstractAbstractFactory
{
    const KEY = self::class;

    const DEFAULT_CLASS = MetricsMiddleware::class;

    const KEY_METRIC_PROVIDERS = 'metricProviders';

    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $this->getServiceConfig($container, $requestedName);

        $metricProvidersField = static::KEY_METRIC_PROVIDERS;

        if (!isset($config[static::KEY_METRIC_PROVIDERS])) {
            throw new ServiceNotCreatedException("Dependency '$metricProvidersField' is not set in config");
        }

        $metricProviderClasses = $config[static::KEY_METRIC_PROVIDERS];

        $metricProviders = [];

        foreach ($metricProviderClasses as $metricProviderClass) {
            $metricProvider = $container->get($metricProviderClass);
            if (!$metricProvider instanceof MetricsProviderInterface) {
                throw new ServiceNotCreatedException("Dependency '$metricProvidersField' contains object that is not implementing required interface");
            }
            $metricProviders[] = $metricProvider;
        }

        $class = $config[static::KEY_CLASS] ?? static::DEFAULT_CLASS;

        return new $class($metricProviders);
    }
}
