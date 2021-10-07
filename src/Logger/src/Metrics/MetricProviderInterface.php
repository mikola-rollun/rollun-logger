<?php

namespace rollun\logger\Metrics;

use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;

interface MetricProviderInterface
{
    public function getMetric(): ProvidesMetricLines;
}