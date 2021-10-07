<?php

namespace rollun\logger\Metrics\Factory;

use Interop\Container\ContainerInterface;
use rollun\logger\Metrics\MetricsMiddleware;
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

        if (!isset($config[static::KEY_METRIC_PROVIDERS])) {
            $fieldName = static::KEY_METRIC_PROVIDERS;
            throw new ServiceNotCreatedException("Dependency '$fieldName' is not set in config");
        }

        $metricProviderClasses = $config[static::KEY_METRIC_PROVIDERS];

        $metricProviders = [];

        foreach ($metricProviderClasses as $metricProviderClass) {
            $metricProviders[] = $container->get($metricProviderClass);
        }

        $class = $config[static::KEY_CLASS] ?? static::DEFAULT_CLASS;

        return new $class($metricProviders);
    }
}
