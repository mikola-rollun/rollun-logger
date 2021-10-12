<?php


namespace rollun\logger\Metrics\Callback;


use rollun\logger\LifeCycleToken;

class ClearOldProcessFilesCallback
{
    public function __construct()
    {

    }

    public function __invoke()
    {
        $this->processTracker->clearOldFiles();
    }
}