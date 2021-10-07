<?php

namespace rollun\logger\Metrics;

use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\Label;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;

class FailedProcessesMetricProvider implements MetricProvider
{
    /** @var string */
    protected $dirPath;

    public function __construct(string $dirPath)
    {
        $this->dirPath = $dirPath;
    }

    /**
     * @throws \Exception
     */
    public function getMetric(): ProvidesMetricLines
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->dirPath, \RecursiveDirectoryIterator::SKIP_DOTS));

        return GaugeCollection::fromGauges(
            MetricName::fromString( 'failed_processes' ),
            Gauge::fromValue( iterator_count($files) )->withLabels(
                Label::fromNameAndValue( 'service_name', $this->getServiceName() )
            )
        );
    }

    /**
     * @throws \Exception
     */
    protected function getServiceName(): string
    {
        $serviceName = exec('hostname');

        if ($serviceName === false) {
            throw new \Exception("Can't get service name");
        }

        $serviceNameParts = explode('.', $serviceName);

        if (!empty($serviceNameParts)) {
            $serviceName = $serviceNameParts[0];
        }

        return str_replace('-', '_', $serviceName);
    }
}