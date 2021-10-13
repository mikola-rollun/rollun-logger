<?php

namespace rollun\metrics;

use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;

interface MetricProviderInterface
{
    public function getMetric(): ProvidesMetricLines;
}