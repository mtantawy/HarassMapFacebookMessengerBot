<?php

require_once __DIR__.'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\WebhookRequestHandler;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use HarassMapFbMessengerBot\Handlers\GetStartedHandler;

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
    die();
}

$dbConnectionParams = require('migrations-db.php');
$dbConnection = DriverManager::getConnection($dbConnectionParams);

$events = $webookHandler->getAllCallbackEvents();

foreach ($events as $event) {
    if ($event instanceof MessageEvent) {
        $response = $messenger->sendMessage($event->getSenderId(), 'Got: '.$event->getMessageText());
    } elseif ($event instanceof PostbackEvent) {
        if ($event->getPostbackPayload() === 'get_started') {
            $getStartedHandler = new GetStartedHandler($messenger, $event, $dbConnection);
            $getStartedHandler->handle();
        }
    }
}
