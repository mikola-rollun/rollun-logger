<?php

namespace rollun\metrics;

use OpenMetricsPhp\Exposition\Text\Collections\CounterCollection;
use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;
use OpenMetricsPhp\Exposition\Text\Metrics\Counter;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\Label;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;

class CallbackMetricProvider implements MetricProviderInterface
{
    use GetServiceName;

    const METRIC_TYPE_GAUGE = 'gauge';
    const METRIC_TYPE_COUNTER = 'counter';

    /** @var callable */
    protected $callback;

    /** @var string */
    protected $metricName;

    /** @var string */
    protected $metricType;

    /** @var string */
    protected $labelName;

    /** @var string */
    protected $labelValue;

    /**
     * @throws \Exception
     */
    public function __construct(
        callable $callback,
        string $metricName,
        string $metricType = self::METRIC_TYPE_GAUGE,
        string $labelName = 'service_name',
        string $labelValue = null
    ) {
        $this->callback = $callback;
        $this->metricName = $metricName;
        $this->metricType = $metricType;
        $this->labelName = $labelName;
        $this->labelValue = $labelName === 'service_name' && is_null($labelValue) ? $this->getServiceName() : $labelValue;
    }

    /**
     * @throws \Exception
     */
    public function getMetric(): ProvidesMetricLines
    {
        $metricValue = ($this->callback)();
        $metricName = MetricName::fromString($this->metricName);
        $label = Label::fromNameAndValue( $this->labelName, $this->labelValue );

        switch ($this->metricType) {
            case self::METRIC_TYPE_GAUGE:
                return GaugeCollection::fromGauges(
                    $metricName,
                    Gauge::fromValue($metricValue)->withLabels($label)
                );
            case self::METRIC_TYPE_COUNTER:
                return CounterCollection::fromCounters(
                    $metricName,
                    Counter::fromValue($metricValue)->withLabels($label)
                );
            default:
                throw new \Exception("Unsupported metric type");
        }
    }
}