<?php

namespace rollun\logger\Metrics;

use OpenMetricsPhp\Exposition\Text\Collections\CounterCollection;
use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;
use OpenMetricsPhp\Exposition\Text\Metrics\Counter;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\Label;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;
use rollun\callback\Callback\SerializedCallback;

class CallbackMetricProvider implements MetricProviderInterface
{
    /** @var SerializedCallback */
    protected $callback;

    /** @var string */
    protected $metricName;

    /** @var string */
    protected $metricType;

    /** @var string */
    protected $labelName;

    /** @var string */
    protected $labelValue;

    public function __construct(
        SerializedCallback $callback,
        string $metricName,
        string $metricType,
        string $labelName = null,
        string $labelValue = null
    ) {
        $this->callback = $callback;
        $this->metricName = $metricName;
        $this->metricType = $metricType;
        if (is_null($labelName) && is_null($labelValue)) {
            $this->labelName = 'service_name';
            $this->labelValue = $this->getServiceName();
        } else {
            $this->labelName = $labelName;
            $this->labelValue = $labelValue;
        }
    }

    /**
     * @throws \Exception
     */
    public function getMetric(): ProvidesMetricLines
    {
        $callback = $this->callback;
        $label = Label::fromNameAndValue( $this->labelName, $this->labelValue );

        switch ($this->metricType) {
            case 'gauge':
                return GaugeCollection::fromGauges(
                    MetricName::fromString($this->metricName),
                    Gauge::fromValue($callback())->withLabels($label)
                );
            case 'counter':
                return CounterCollection::fromCounters(
                    MetricName::fromString($this->metricName),
                    Counter::fromValue($callback())->withLabels($label)
                );
            default:
                throw new \Exception();
        }
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