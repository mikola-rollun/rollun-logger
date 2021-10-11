# Отдача метрик в Прометеус по запросу

## Общая схема

Прометеус запрашивает url метрики, например `/metrics`, и получает значения всех метрик в нужном формате. Для этого реализован контроллер `MetricsMiddleware`, в который можно добавлять метрики и получать их значения.

Чтобы добавить метрику в `MetricsMiddleware`, нужно создать для нее провайдер, который реализует интерфейс `MetricProviderInterface`.

Интерфейс `MetricProviderInterface` требует возвращения данных в формате библиотеки https://github.com/openmetrics-php/exposition-text.

### Пример конфига:
```
use rollun\logger\Metrics\Factory\MetricsMiddlewareAbstractFactory;
use rollun\logger\Metrics\MetricsMiddleware;

MetricsMiddlewareAbstractFactory::KEY => [
    MetricsMiddleware::class => [
        MetricsMiddlewareAbstractFactory::KEY_CLASS => MetricsMiddleware::class,
        MetricsMiddlewareAbstractFactory::KEY_METRIC_PROVIDERS => [
            'FailedProcessesMetricProvider',
            // other providers...
        ],
    ],
],
```

### Пример роута

```
   use rollun\logger\Metrics\MetricsMiddleware;

   $app->get(
        '/metrics',
        MetricsMiddleware::class,
        'metrics'
    );
```