<?php

require_once __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\WebhookRequestHandler;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use HarassMapFbMessengerBot\Handlers\GetStartedHandler;
use HarassMapFbMessengerBot\Handlers\ReportIncidentHandler;
use HarassMapFbMessengerBot\Handlers\GetIncidentsHandler;
use Tgallice\FBMessenger\Exception\ApiException;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['PAGE_TOKEN', 'VERIFY_TOKEN']);

$pageToken = getenv('PAGE_TOKEN');
$verifyToken = getenv('VERIFY_TOKEN');

$messenger = Messenger::create($pageToken);

$webookHandler = new WebhookRequestHandler($pageToken, $verifyToken);

if ($webookHandler->isValidVerifyTokenRequest()) {
    header("HTTP/1.1 200 OK");
    echo $webookHandler->getChallenge();
}

$dbConnectionParams = require('migrations-db.php');
$dbConnection = DriverManager::getConnection($dbConnectionParams);

$events = $webookHandler->getAllCallbackEvents();

try {
    foreach ($events as $event) {
        if ($event instanceof MessageEvent) {
            if ($event->isQuickReply() && 0 === mb_strpos($event->getQuickReplyPayload(), 'GET_INCIDENTS')) {
                $eventHandler = new GetIncidentsHandler($messenger, $event, $dbConnection);
            } else {
                $eventHandler = new ReportIncidentHandler($messenger, $event, $dbConnection);
            }
        } elseif ($event instanceof PostbackEvent && $event->getPostbackPayload() === 'GET_STARTED') {
            $eventHandler = new GetStartedHandler($messenger, $event, $dbConnection);
        }
        if (isset($eventHandler)) {
            $eventHandler->handle();
        }
    }
} catch (ApiException | Exception $e) {
    header("HTTP/1.1 200 OK");
}
