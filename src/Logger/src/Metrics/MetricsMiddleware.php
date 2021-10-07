<?php

namespace rollun\logger\Metrics;

use OpenMetricsPhp\Exposition\Text\HttpResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use rollun\dic\InsideConstruct;

class MetricsMiddleware implements MiddlewareInterface
{
    /** @var array<MetricProvider> */
    protected $metricProviders;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @throws \ReflectionException
     */
    public function __construct(array $metricProviders, LoggerInterface $logger = null)
    {
        $this->metricProviders = $metricProviders;
        InsideConstruct::init([
            'logger' => LoggerInterface::class,
        ]);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $metrics = [];

        foreach ($this->metricProviders as $metricProvider) {
            try {
                $metrics[] = $metricProvider->getMetric();
            } catch (\Throwable $e) {
                $this->logger->warning("Can't get metric", [
                    'exception' => $e
                ]);
                continue;
            }
        }

        // 404 response
        if (empty($metrics)) {
            return $handler->handle($request);
        }

        return HttpResponse::fromMetricCollections( ...$metrics )
            ->withHeader( 'Content-Type', 'text/plain; charset=utf-8' );
    }
}
