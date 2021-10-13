<?php

namespace rollun\metrics;

use rollun\metrics\Callback\ClearOldProcessesDataCallback;
use rollun\metrics\Callback\GetFailedProcessesCountCallback;
use rollun\metrics\Factory\CallbackMetricProviderAbstractFactory;
use rollun\metrics\Factory\FilesCountMetricProviderAbstractFactory;
use rollun\metrics\Factory\MetricsMiddlewareAbstractFactory;
use rollun\metrics\Factory\ProcessTrackerFactory;

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
            'aliases' => [
                ProcessTrackerInterface::class => ProcessTracker::class,
            ],
            'invokables' => [
                ClearOldProcessesDataCallback::class => ClearOldProcessesDataCallback::class,
                GetFailedProcessesCountCallback::class => GetFailedProcessesCountCallback::class,
            ],
            'factories' => [
                ProcessTracker::class => ProcessTrackerFactory::class,
            ],
            'abstract_factories' => [
                MetricsMiddlewareAbstractFactory::class,
                FilesCountMetricProviderAbstractFactory::class,
                CallbackMetricProviderAbstractFactory::class,
            ],
        ];
    }
}
