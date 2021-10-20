<?php

namespace rollun\metrics;

use rollun\metrics\Callback\ClearOldProcessesDataCallback;
use rollun\metrics\Factory\MetricsMiddlewareAbstractFactory;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            MetricsMiddlewareAbstractFactory::KEY => [
                MetricsMiddleware::class => [
                    MetricsMiddlewareAbstractFactory::KEY_CLASS => MetricsMiddleware::class,
                    MetricsMiddlewareAbstractFactory::KEY_METRIC_PROVIDERS => [
                        ProcessTracker::class,
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'aliases' => [
                ProcessTrackerInterface::class => ProcessTracker::class,
            ],
            'invokables' => [
                ClearOldProcessesDataCallback::class => ClearOldProcessesDataCallback::class,
                ProcessTracker::class => ProcessTracker::class,
            ],
            'factories' => [
            ],
            'abstract_factories' => [
                MetricsMiddlewareAbstractFactory::class,
            ],
        ];
    }
}
