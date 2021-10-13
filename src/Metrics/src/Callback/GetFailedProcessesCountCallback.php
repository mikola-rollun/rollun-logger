<?php


namespace rollun\metrics\Callback;


use rollun\dic\InsideConstruct;
use rollun\metrics\ProcessTrackerInterface;

class GetFailedProcessesCountCallback
{
    /** @var ProcessTrackerInterface */
    protected $processTracker;

    public function __construct(ProcessTrackerInterface $processTracker)
    {
        InsideConstruct::init([
            'processTracker' => ProcessTrackerInterface::class,
        ]);
    }

    public function __invoke(): int
    {
        return $this->processTracker->getFailedProcessesCount();
    }
}