<?php

namespace rollun\metrics;

interface ProcessTrackerInterface
{
    public function storeProcessData();

    public function clearProcessData();

    public function clearOldProcessesData();

    public function getFailedProcessesCount(): int;
}