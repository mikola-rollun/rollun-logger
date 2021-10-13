<?php

namespace rollun\metrics\Factory;

use Interop\Container\ContainerInterface;
use rollun\metrics\CallbackMetricProvider;
use rollun\utils\Factory\AbstractAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class CallbackMetricProviderAbstractFactory extends AbstractAbstractFactory
{
    const KEY = self::class;

    const DEFAULT_CLASS = CallbackMetricProvider::class;

    const KEY_CALLBACK = 'callback';
    const KEY_METRIC_NAME = 'metricName';
    const KEY_METRIC_TYPE = 'metricType';
    const KEY_LABEL_NAME = 'labelName';
    const KEY_LABEL_TYPE = 'labelType';

    /**
     * @param string $requestedName
     * @throws \Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $this->getServiceConfig($container, $requestedName);

        if (!isset($config[static::KEY_CALLBACK])) {
            $fieldName = static::KEY_CALLBACK;
            throw new ServiceNotCreatedException("Field '$fieldName' is not set in config");
        }

        if (!isset($config[static::KEY_METRIC_NAME])) {
            $fieldName = static::KEY_METRIC_NAME;
            throw new ServiceNotCreatedException("Field '$fieldName' is not set in config");
        }

        $callback = $config[static::KEY_CALLBACK];
        $metricName = $config[static::KEY_METRIC_NAME];

        return new CallbackMetricProvider($callback, $metricName);
    }
}
