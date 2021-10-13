<?php

namespace rollun\metrics;

use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\Label;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;

class FilesCountMetricProvider implements MetricProviderInterface
{
    use GetServiceName;

    /** @var string */
    protected $metricName;

    /** @var string */
    protected $dirPath;

    public function __construct(string $metricName, string $dirPath)
    {
        $this->metricName = $metricName;
        $this->dirPath = $dirPath;
    }

    /**
     * @throws \Exception
     */
    public function getMetric(): ProvidesMetricLines
    {
        $filesCount = exec("find $this->dirPath -type f | wc -l");

        if ($filesCount === false) {
            throw new \Exception("Can't get files count for dir '$this->dirPath'");
        }

        return GaugeCollection::fromGauges(
            MetricName::fromString( $this->metricName ),
            Gauge::fromValue( $filesCount )->withLabels(
                Label::fromNameAndValue( 'service_name', $this->getServiceName() )
            )
        );
    }
}