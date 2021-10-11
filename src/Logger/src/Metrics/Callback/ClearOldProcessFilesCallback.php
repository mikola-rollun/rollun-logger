<?php


namespace rollun\logger\Metrics\Callback;


use rollun\logger\LifeCycleToken;

class ClearOldProcessFilesCallback
{
    public function __invoke()
    {
        $dirPath = getenv('PROCESS_TRACKING_DIR') ?: LifeCycleToken::PROCESS_TRACKING_DIR;

        $dirsByDate = glob($dirPath . '*', GLOB_ONLYDIR);

        if ($dirsByDate === false) {
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
}