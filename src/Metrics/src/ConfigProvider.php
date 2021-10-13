<?php

namespace rollun\metrics;

use rollun\metrics\Callback\ClearOldProcessesDataCallback;
use rollun\metrics\Callback\GetFailedProcessesCountCallback;
use rollun\metrics\Factory\CallbackMetricProviderAbstractFactory;
use rollun\metrics\Factory\FilesCountMetricProviderAbstractFactory;
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
                        'FailedProcessesCountMetricProvider',
                    ],
                ],
            ],
            CallbackMetricProviderAbstractFactory::KEY => [
                'FailedProcessesCountMetricProvider' => [
                    CallbackMetricProviderAbstractFactory::KEY_CLASS => CallbackMetricProvider::class,
                    CallbackMetricProviderAbstractFactory::KEY_METRIC_NAME => 'failed_processes',
                    CallbackMetricProviderAbstractFactory::KEY_CALLBACK => function() {
                        $callback = new GetFailedProcessesCountCallback();
                        return $callback();
                    }
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        return [
            'abstract_factories' => [
                MetricsMiddlewareAbstractFactory::class,
                FilesCountMetricProviderAbstractFactory::class,
                CallbackMetricProviderAbstractFactory::class,
            ],
            'invokables' => [
                ProcessTracker::class => ProcessTracker::class,
                ClearOldProcessesDataCallback::class => ClearOldProcessesDataCallback::class,
                GetFailedProcessesCountCallback::class => GetFailedProcessesCountCallback::class,
            ],
            'aliases' => [
                ProcessTrackerInterface::class => ProcessTracker::class,
            ],
        ];
    }
}
