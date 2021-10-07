<?php

namespace rollun\logger\Metrics\Factory;

use Interop\Container\ContainerInterface;
use rollun\logger\Metrics\FilesCountMetricProvider;
use rollun\utils\Factory\AbstractAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class FilesCountMetricProviderAbstractFactory extends AbstractAbstractFactory
{
    const KEY = self::class;

    const DEFAULT_CLASS = FilesCountMetricProvider::class;

    const KEY_METRIC_NAME = 'metricName';
    const KEY_DIR_PATH = 'dirPath';

    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $this->getServiceConfig($container, $requestedName);

        if (!isset($config[static::KEY_METRIC_NAME])) {
            $fieldName = static::KEY_METRIC_NAME;
            throw new ServiceNotCreatedException("Field '$fieldName' is not set in config");
        }

        if (!isset($config[static::KEY_DIR_PATH])) {
            $fieldName = static::KEY_DIR_PATH;
            throw new ServiceNotCreatedException("Field '$fieldName' is not set in config");
        }

        $metricName = $config[static::KEY_METRIC_NAME];
        $dirPath = $config[static::KEY_DIR_PATH];

        return new FilesCountMetricProvider($metricName, $dirPath);
    }
}
