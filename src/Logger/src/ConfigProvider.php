<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */

namespace rollun\logger;

use Psr\Log\LoggerInterface;
use rollun\logger\Factory\LifeCycleTokenFactory;
use rollun\logger\Formatter\ContextToString;
use rollun\logger\Formatter\FluentdFormatter;
use rollun\logger\Formatter\LogStashFormatter;
use rollun\logger\Formatter\SlackFormatter;
use rollun\logger\Metrics\Factory\FailedProcessesMetricProviderAbstractFactory;
use rollun\logger\Metrics\Factory\MetricsMiddlewareAbstractFactory;
use rollun\logger\Metrics\FailedProcessesMetricProvider;
use rollun\logger\Metrics\MetricsMiddleware;
use rollun\logger\Middleware\Factory\RequestLoggedMiddlewareFactory;
use rollun\logger\Middleware\RequestLoggedMiddleware;
use rollun\logger\Processor\ExceptionBacktrace;
use rollun\logger\Processor\Factory\LifeCycleTokenReferenceInjectorFactory;
use rollun\logger\Processor\IdMaker;
use rollun\logger\Processor\LifeCycleTokenInjector;
use rollun\logger\Prometheus\Collector;
use rollun\logger\Prometheus\PushGateway;
use rollun\logger\Services\JsonTruncator;
use rollun\logger\Writer\Factory\PrometheusFactory;
use rollun\logger\Writer\PrometheusWriter;
use rollun\logger\Writer\Slack;
use rollun\logger\Writer\Stream;
use rollun\logger\Writer\Udp;
use rollun\logger\Writer\HttpAsyncMetric;
use rollun\logger\Formatter\Metric;
use Zend\ServiceManager\Factory\InvokableFactory;

class ConfigProvider
{
    /**
     * Return default logger config
     */
    public function __invoke()
    {
        $config = [
            "dependencies"   => $this->getDependencies(),
            "log"            => $this->getLog(),
            'log_processors' => $this->getLogProcessors(),
            'log_formatters' => $this->getLogFormatters(),
            'log_writers'    => $this->getLogWriters(),
        ];

        $metricsConfig = $this->getMetricsConfig();

        return array_merge($config, $metricsConfig);
    }

    protected function getLogProcessors()
    {
        return [
            'factories' => [
                LifeCycleTokenInjector::class => LifeCycleTokenReferenceInjectorFactory::class,
                IdMaker::class                => InvokableFactory::class
            ],
        ];
    }

    protected function getLogFormatters()
    {
        return [
            'factories' => [
                ContextToString::class  => InvokableFactory::class,
                FluentdFormatter::class => InvokableFactory::class,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getLogWriters()
    {
        return [
            'factories' => [
                PrometheusWriter::class => PrometheusFactory::class,
            ],
        ];
    }

    /**
     * Return dependencies config
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            'abstract_factories' => [
                LoggerAbstractServiceFactory::class,
                MetricsMiddlewareAbstractFactory::class,
                FailedProcessesMetricProviderAbstractFactory::class,
            ],
            'factories'          => [
                Logger::class         => LoggerServiceFactory::class,
                'LogFilterManager'    => FilterPluginManagerFactory::class,
                'LogFormatterManager' => FormatterPluginManagerFactory::class,
                'LogProcessorManager' => ProcessorPluginManagerFactory::class,
                'LogWriterManager'    => WriterPluginManagerFactory::class,

                //LifeCycleToken
                LifeCycleToken::class => LifeCycleTokenFactory::class,

                // Middlewares
                RequestLoggedMiddleware::class => RequestLoggedMiddlewareFactory::class
            ],
            'invokables'         => [
                PushGateway::class => PushGateway::class,
                Collector::class   => Collector::class
            ],
            'aliases'            => [],
        ];
    }

    /**
     * Return default config for logger.
     *
     * @return array
     */
    public function getLog()
    {
        return [
            LoggerInterface::class => [
                'writers'    => [
                    'stream_stdout' => [
                        'name'    => Stream::class,
                        'options' => [
                            'stream'    => 'php://stdout',
                            'formatter' => FluentdFormatter::class
                        ],
                    ],
                    'udp_logstash' => [
                        'name' => Udp::class,

                        'options' => [
                            'client'    => [
                                'host' => getenv('LOGSTASH_HOST'),
                                'port' => getenv('LOGSTASH_PORT'),
                                'options' => [
                                    'timeout' => 10,
                                    'writingTimeout' => 10,
                                    'connectionTimeout' => 10,
                                ]
                            ],
                            'formatter' => new LogStashFormatter(
                                getenv("LOGSTASH_INDEX"),
                                [
                                    'timestamp'              => '@timestamp',
                                    'message'                => 'message',
                                    'level'                  => 'level',
                                    'priority'               => 'priority',
                                    'context'                => 'context',
                                    'lifecycle_token'        => 'lifecycle_token',
                                    'parent_lifecycle_token' => 'parent_lifecycle_token',
                                    '_index_name'            => '_index_name'
                                ],
                                new JsonTruncator(LogStashFormatter::DEFAULT_MAX_SIZE)
                            ),
                            'filters'   => [
                                'priority_<_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<',
                                        'priority' => 4,
                                    ],
                                ],
                                'regex_not_metrics' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^((?!METRICS).)*$/'
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'http_async_metric_metrics' => [
                        'name'    => HttpAsyncMetric::class,
                        'options' => [
                            'url'       => getenv('METRIC_URL'),
                            'filters'   => [
                                'priority_>=_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => 4, // we should send only warnings or notices
                                    ],
                                ],
                                'priority_<=_5' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<=',
                                        'priority' => 5, // we should send only warnings or notices
                                    ],
                                ],
                                'regex_only_metrics' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^METRICS$/'
                                    ],
                                ],
                            ],
                            'formatter' => Metric::class,
                        ],
                    ],
                    'prometheus_metrics_gauge' => [
                        PrometheusFactory::COLLECTOR => Collector::class, // не обязательный параметр.
                        PrometheusFactory::JOB_NAME  => 'logger_job',  // не обязательный параметр.
                        'name'                       => PrometheusWriter::class,
                        'options'                    => [
                            PrometheusFactory::TYPE => PrometheusFactory::TYPE_GAUGE,
                            'filters'               => [
                                'regex_only_metrics_gauge' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^METRICS_GAUGE$/'
                                    ],
                                ],
                                'priority_>=_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => 4, // we should send only warnings or notices
                                    ],
                                ],
                                'priority_<=_5' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<=',
                                        'priority' => 5, // we should send only warnings or notices
                                    ],
                                ],
                            ]
                        ],
                    ],
                    'prometheus_metrics_counter' => [
                        'name'    => PrometheusWriter::class,
                        'options' => [
                            PrometheusFactory::TYPE => PrometheusFactory::TYPE_COUNTER,
                            'filters'               => [
                                'regex_only_metrics_counter' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^METRICS_COUNTER$/'
                                    ],
                                ],
                                'priority_>=_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '>=',
                                        'priority' => 4, // we should send only warnings or notices
                                    ],
                                ],
                                'priority_<=_5' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<=',
                                        'priority' => 5, // we should send only warnings or notices
                                    ],
                                ],
                            ]
                        ],
                    ],
                    'slack' => [
                        'name'    => Slack::class,
                        'options' => [
                            'token'     => getenv('SLACK_TOKEN'),
                            'channel'   => getenv('SLACK_CHANNEL'),
                            'filters'   => [
                                'regex_not_metrics' => [
                                    'name'    => 'regex',
                                    'options' => [
                                        'regex' => '/^((?!METRICS).)*$/'
                                    ],
                                ],
                                'priority_<_4' => [
                                    'name'    => 'priority',
                                    'options' => [
                                        'operator' => '<',
                                        'priority' => 4,
                                    ],
                                ],
                            ],
                            'formatter' => SlackFormatter::class,
                        ],
                    ],
                ],
                'processors' => [
                    [
                        'name' => IdMaker::class,
                    ],
                    [
                        'name' => ExceptionBacktrace::class
                    ],
                    [
                        'name' => LifeCycleTokenInjector::class,
                    ],
                ],
            ],
        ];
    }

    protected function getMetricsConfig(): array
    {
        return [
            MetricsMiddlewareAbstractFactory::KEY => [
                MetricsMiddleware::class => [
                    MetricsMiddlewareAbstractFactory::KEY_CLASS => MetricsMiddleware::class,
                    MetricsMiddlewareAbstractFactory::KEY_METRIC_PROVIDERS => [
                        FailedProcessesMetricProvider::class,
                    ],
                ],
            ],
            FailedProcessesMetricProviderAbstractFactory::KEY => [
                FailedProcessesMetricProvider::class => [
                    FailedProcessesMetricProviderAbstractFactory::KEY_CLASS => FailedProcessesMetricProvider::class,
                    FailedProcessesMetricProviderAbstractFactory::KEY_DIR_PATH => 'data/process-tracking',
                ],
            ],
        ];
    }
}
