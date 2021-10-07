<?php

namespace rollun\logger\Metrics;

use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;

interface MetricProvider
{
    public function getMetric(): ProvidesMetricLines;
}