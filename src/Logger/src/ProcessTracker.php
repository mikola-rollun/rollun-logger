<?php

namespace rollun\logger;

class ProcessTracker
{
    private const PROCESS_TRACKING_DIR = 'data/process-tracking/';

    /** @var LifeCycleToken */
    protected $lifeCycleToken;

    /** @var string */
    protected $filePath;

    public function __construct(LifeCycleToken $lifeCycleToken)
    {
        $this->lifeCycleToken = $lifeCycleToken;
    }

    public function createFile()
    {
        $dirPath = $this->getProcessTrackingDir();

        $dirPath .= (new \DateTime())->format('Y-m-d') . '/';

        if (!file_exists($dirPath)) {
            $isDirCreated = mkdir($dirPath, 0777, true);
            if (!$isDirCreated) {
                return;
            }
        }

        $this->filePath = $dirPath . $this->lifeCycleToken->toString();

        $requestInfo = '';

        if (!empty($this->lifeCycleToken->getParentToken())) {
            $requestInfo .= 'parent_lifecycle_token: ' . $this->lifeCycleToken->getParentToken() . PHP_EOL;
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $requestInfo .= 'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $requestInfo .= 'REQUEST_URI: ' . $_SERVER['REQUEST_URI'] . PHP_EOL;
        }

        file_put_contents($this->filePath, $requestInfo);
    }

    public function removeFile()
    {
        if (!is_string($this->filePath)) {
            return;
        }
        unlink($this->filePath);
    }

    /**
     * @throws \Exception
     */
    public function getFilesCount(): string
    {
        $dirPath = $this->getProcessTrackingDir();

        $filesCount = exec("find $dirPath -type f | wc -l");

        if ($filesCount === false) {
            throw new \Exception("Can't get files count for dir '$dirPath'");
        }

        return $filesCount;
    }

    public function clearOldFiles()
    {
        $dirPath = $this->getProcessTrackingDir();

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

    protected function getProcessTrackingDir(): string
    {
        return self::PROCESS_TRACKING_DIR;
    }
}