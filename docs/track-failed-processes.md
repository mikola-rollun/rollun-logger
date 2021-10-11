# Отслеживание упавших процессов

## Содержание
- [Описание](#описание)
- [Подключение](#подключение)
  + [Создание и удаление файла](#создание-и-удаление-файла)
  + [Роут для сбора метрики](#роут-для-сбора-метрики)
  + [Крон для удаления старых файлов](#крон-для-удаления-старых-файлов)
- [Пример index.php](#пример-indexphp)
- [Структура файла](#структура-файла)

## Описание
Иногда процессы падают без логов, например, при перерасходе памяти процесс просто убивается OS. Чтобы осталась какая-то информация о таких случаях, было сделано следующее:
1. при старте приложения создается файл с инфой о процессе,
2. при успешном завершении он удаляется,
3. если процесс упал, не дойдя до нормального завершения, то файл соответственно остается, и из него можно получить инфу об упавшем процессе,
4. количество оставшихся файлов пишется в метрику, которую можно посмотреть в Графане,
5. файлы старше месяца подчищаются кроном.

## Подключение

Чтобы подключить этот функционал в сервисе нужно:
1. Задать папку для хранения файлов,
2. [Добавить создание и удаление файла](#создание-и-удаление-файла) в `index.php` (или др.),
3. [Добавить роут для сбора метрики](#роут-для-сбора-метрики),
4. [Добавить крон для удаления старых файлов](#крон-для-удаления-старых-файлов).

### Создание и удаление файла
[Пример готового index.php](#пример-indexphp)

В `index.php` (или другом корневом скрипте) нужно выполнить такие действия:
1. В самом начале явно добавить создание `LifeCycleToken`:
    ```
    $lifeCycleToken = LifeCycleToken::createFromHeaders();
    
    // или для консоли:
    $lifeCycleToken = LifeCycleToken::createFromArgv();
    ```
2. В созданном `$lifeCycleToken` вызвать метод `createFile()` и передать в него путь к директории, в которой должны храниться создаваемые файлы:
   ```
   $lifeCycleToken->createFile('data/process-tracking/');
   ```
3. Добавить `$lifeCycleToken` в контейнер:
   ```
   $container->setService(LifeCycleToken::class, $lifeCycleToken);
   ```
4. В самом конце `index.php` вызывать удаление файла:
   ```
   $lifeCycleToken->removeFile();
   ```
   
### Роут для сбора метрики

[Подробная настройка метрики](https://github.com/rollun-com/rollun-logger/blob/master/docs/pull-metrics.md)

В `MetricsMiddleware` настроен подсчет файлов, нужно только добавить роут в `routes.php` и прописать его в Прометеусе:

```
   use rollun\logger\Metrics\MetricsMiddleware;

   $app->get(
        '/metrics',
        MetricsMiddleware::class,
        'metrics'
    );
```

### Крон для удаления старых файлов

Реализован колбек `ClearOldProcessFilesCallback`, его нужно только подключить в крон.

```
use rollun\logger\Metrics\Callback\ClearOldProcessFilesCallback;

return [
    SerializedCallbackAbstractFactory::class => [
        'clearOldProcessFiles' => ClearOldProcessFilesCallback::class,
    ],
    CallbackAbstractFactoryAbstract::KEY => [
        'min_multiplexer' => [
            MultiplexerAbstractFactory::KEY_CLASS => Multiplexer::class,
            MultiplexerAbstractFactory::KEY_CALLBACKS_SERVICES => [
               'clearOldProcessFilesCron',
            ],
        ],
        'clearOldProcessFilesCron' => [
            CronExpressionAbstractFactory::KEY_CLASS => CronExpression::class,
            CronExpressionAbstractFactory::KEY_EXPRESSION => "0 1 * * *",
            CronExpressionAbstractFactory::KEY_CALLBACK_SERVICE => 'clearOldProcessFiles',
        ],
    ],
    InterruptAbstractFactoryAbstract::KEY => [
        'cron' => [
            ProcessAbstractFactory::KEY_CLASS => Process::class,
            ProcessAbstractFactory::KEY_CALLBACK_SERVICE => 'min_multiplexer',
        ],
    ],
];
```

## Пример index.php
```
<?php

use rollun\logger\LifeCycleToken;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;
use Zend\ServiceManager\ServiceManager;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$lifeCycleToken = LifeCycleToken::createFromHeaders();
$lifeCycleToken->createFile('data/process-tracking/');

/** @var ServiceManager $container */
$container = require 'config/container.php';

$container->setService(LifeCycleToken::class, $lifeCycleToken);

/** @var Application $app */
$app = $container->get(Application::class);
$factory = $container->get(MiddlewareFactory::class);

// Execute programmatic/declarative middleware pipeline and routing
// configuration statements
(require 'config/pipeline.php')($app, $factory, $container);
(require 'config/routes.php')($app, $factory, $container);

$app->run();

$lifeCycleToken->removeFile();
```

## Структура файла
Название файла - текущий `LifeCycleToken`.

В сам файл записываются такие данные (если они есть):
* `parent_lifecycle_token`
* `$_SERVER['REMOTE_ADDR']`
* `$_SERVER['REQUEST_URI']`
