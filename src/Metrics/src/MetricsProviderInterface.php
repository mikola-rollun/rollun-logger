<?php

namespace rollun\metrics;

use OpenMetricsPhp\Exposition\Text\Interfaces\ProvidesMetricLines;

interface MetricsProviderInterface
{
    /** @return array<ProvidesMetricLines> */
    public function getMetrics(): array;
}