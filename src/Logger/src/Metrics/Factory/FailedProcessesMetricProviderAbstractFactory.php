<?php

namespace rollun\logger\Metrics\Factory;

use Interop\Container\ContainerInterface;
use rollun\logger\Metrics\FailedProcessesMetricProvider;
use rollun\utils\Factory\AbstractAbstractFactory;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class FailedProcessesMetricProviderAbstractFactory extends AbstractAbstractFactory
{
    const KEY = self::class;

    const DEFAULT_CLASS = FailedProcessesMetricProvider::class;

    const KEY_DIR_PATH = 'dirPath';

    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $this->getServiceConfig($container, $requestedName);

        if (!isset($config[static::KEY_DIR_PATH])) {
            $fieldName = static::KEY_DIR_PATH;
            throw new ServiceNotCreatedException("Field '$fieldName' is not set in config");
        }

        $dirPath = $config[static::KEY_DIR_PATH];

        return new FailedProcessesMetricProvider($dirPath);
    }
}
