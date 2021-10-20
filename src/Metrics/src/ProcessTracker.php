<?php

namespace rollun\metrics;

use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\Label;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;
use rollun\logger\LifeCycleToken;

class ProcessTracker implements ProcessTrackerInterface, MetricsProviderInterface
{
    use GetServiceName;

    private const PROCESS_TRACKING_DIR = 'data/process-tracking/';

    /** @var string */
    protected static $filePath;

    /**
     * TODO: make $lifeCycleToken optional
     */
    public static function storeProcessData(LifeCycleToken $lifeCycleToken)
    {
        $dirPath = static::getProcessTrackingDir();

        $dirPath .= (new \DateTime())->format('Y-m-d') . '/';

        if (!file_exists($dirPath)) {
            $isDirCreated = mkdir($dirPath, 0777, true);
            if (!$isDirCreated) {
                return;
            }
        }

        static::$filePath = $dirPath . $lifeCycleToken->toString();

        $requestInfo = 'timestamp: ' . time() . PHP_EOL;

        if (!empty($lifeCycleToken->getParentToken())) {
            $requestInfo .= 'parent_lifecycle_token: ' . $lifeCycleToken->getParentToken() . PHP_EOL;
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $requestInfo .= 'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $requestInfo .= 'REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . PHP_EOL;
        }

        file_put_contents(static::$filePath, $requestInfo);
    }

    public static function clearProcessData()
    {
        if (!is_string(static::$filePath)) {
            return;
        }
        unlink(static::$filePath);
    }

    public static function clearOldProcessesData()
    {
        $dirPath = static::getProcessTrackingDir();

        $dirsByDate = glob($dirPath . '*', GLOB_ONLYDIR);

        if (empty($dirsByDate)) {
            return;
        }

        $monthAgo = (new \DateTime())->sub(new \DateInterval('P30D'))->format('Y-m-d');

        // получаем все папки старше 1 месяца
        $dirsToRemove = array_filter($dirsByDate, function ($dateDirPath) use ($monthAgo) {
            $dirName = explode('/', $dateDirPath);
            if (empty($dirName)) {
                return false;
            }
            $dirName = end($dirName);
            // название папки должно быть формата 'Y-m-d', иначе пропускаем
            if (!\DateTime::createFromFormat('Y-m-d', $dirName) instanceof \DateTime) {
                return false;
            }
            return $dirName < $monthAgo;
        });

        foreach ($dirsToRemove as $dateDirPath) {
            exec("rm -rf " . $dateDirPath);
        }
    }

    /**
     * @throws \Exception
     */
    public function getMetrics(): array
    {
        return [
            GaugeCollection::fromGauges(
                MetricName::fromString('failed_processes'),
                Gauge::fromValue(static::getFailedProcessesCount())->withLabels(
                    Label::fromNameAndValue('service_name', static::getServiceName())
                )
            )
        ];
    }

    /**
     * @throws \Exception
     */
    protected static function getFailedProcessesCount(): int
    {
        $dirPath = static::getProcessTrackingDir();

        $filesCount = exec("find $dirPath -type f | wc -l");

        if ($filesCount === false) {
            throw new \Exception("Can't get files count for dir '$dirPath'");
        }

        if (!is_numeric($filesCount)) {
            throw new \Exception("Files count must be numeric");
        }

        return (int)$filesCount;
    }

    protected static function getProcessTrackingDir(): string
    {
        return self::PROCESS_TRACKING_DIR;
    }
}