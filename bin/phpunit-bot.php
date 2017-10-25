<?php

use Tob\PhpUnitBot\Command\CreateFromSourceCommand;
use Tob\PhpUnitBot\Config\BotConfig;
use Zend\Console\Console;
use ZF\Console\Application;
use ZF\Console\Dispatcher;

$autoloadFiles = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../autoload.php',
];
foreach ($autoloadFiles as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        break;
    }
}

define('VERSION', 0.1);

$botConfig = new BotConfig(include __DIR__ . '/../config/config.php');

$dispatcher = new Dispatcher();
$dispatcher->map('create', new CreateFromSourceCommand($botConfig));

$application = new Application(
    'Builder', VERSION, include __DIR__ . '/../config/routes.php', Console::getInstance(), $dispatcher
);
$exit = $application->run();
exit($exit);