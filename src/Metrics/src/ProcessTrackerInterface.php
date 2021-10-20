<?php

namespace rollun\metrics;

use rollun\logger\LifeCycleToken;

interface ProcessTrackerInterface
{
    public static function storeProcessData(LifeCycleToken $lifeCycleToken);

    public static function clearProcessData();

    public static function clearOldProcessesData();
}