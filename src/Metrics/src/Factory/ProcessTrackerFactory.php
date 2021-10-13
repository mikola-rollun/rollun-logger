<?php

namespace rollun\metrics\Factory;

use Interop\Container\ContainerInterface;
use rollun\logger\LifeCycleToken;
use rollun\metrics\ProcessTracker;
use Zend\ServiceManager\Factory\FactoryInterface;

class ProcessTrackerFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $lifeCycleToken = $container->get(LifeCycleToken::class);

        return new ProcessTracker($lifeCycleToken);
    }
}
