<?php

require_once __DIR__.'/vendor/autoload.php';

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Doctrine\DBAL\DriverManager;
use Interop\Container\ContainerInterface;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\WebhookRequestHandler;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use HarassMapFbMessengerBot\ConversationRouter;
use HarassMapFbMessengerBot\Handler\GetStartedHandler;
use HarassMapFbMessengerBot\Handler\ReportIncidentHandler;
use HarassMapFbMessengerBot\Handler\GetIncidentsHandler;
use HarassMapFbMessengerBot\Middleware\UserMiddleware;
use HarassMapFbMessengerBot\Service\UserService;
use HarassMapFbMessengerBot\Service\ReportService;
use HarassMapFbMessengerBot\Controller\ReportController;
use Tgallice\FBMessenger\Exception\ApiException;

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();
$dotenv->required(['PAGE_TOKEN', 'VERIFY_TOKEN']);

$settings = [
    'displayErrorDetails' => (bool) getenv('DEBUG'),
    'addContentLengthHeader' => false
];

$app = new App([
        'settings' => $settings,
        'messenger' => Messenger::create(getenv('PAGE_TOKEN')),
        'webhookHandler' => new WebhookRequestHandler(getenv('PAGE_TOKEN'), getenv('VERIFY_TOKEN')),
        'dbConnection' => function () {
            $dbConnectionParams = require('migrations-db.php');
            return DriverManager::getConnection($dbConnectionParams);
        },
        'logger' => function () {
            $logger = new Logger('debug_logger');
            $file_handler = new StreamHandler('logs/app.log');
            $logger->pushHandler($file_handler);
            return $logger;
        },
        'userService' => function (ContainerInterface $container) {
            return new UserService($container);
        },
        'reportService' => function (ContainerInterface $container) {
            return new ReportService($container);
        },
        'conversationRouter' => function (ContainerInterface $container) {
            return new ConversationRouter($container);
        }
]);

$app->get('/', function (Request $request, Response $response) {
    if ($this->webhookHandler->isValidVerifyTokenRequest()) {
        $response = $response->withStatus(200);
        return $response->write($this->webhookHandler->getChallenge());
    }
});

$app->post('/report/datetime', ReportController::class);

$app->post('/', function (Request $request, Response $response) {
    $this->logger->debug(json_encode($request->getParsedBody()));

    $this->webhookHandler->handleRequest($request);

    $events = $this->webhookHandler->getAllCallbackEvents();

    try {
        foreach ($events as $event) {
            $user = $this->userService->getOrCreateUserByFacebookPSID($event->getSenderId());
            $this->logger->debug(serialize($user));

            switch ($this->conversationRouter->route($event, $user)) {
                case $this->conversationRouter::HANDLER_GET_STARTED:
                       $eventHandler = new GetStartedHandler($this, $event);
                    break;

                case $this->conversationRouter::HANDLER_REPORT_INCIDENT:
                       $eventHandler = new ReportIncidentHandler($this, $event);
                    break;

                case $this->conversationRouter::HANDLER_GET_INCIDENTS:
                       $eventHandler = new GetIncidentsHandler($this, $event);
                    break;

                default:
                       $eventHandler = new GetStartedHandler($this, $event);
                    break;
            }

            if (isset($eventHandler)) {
                $eventHandler->handle();
            }
        }
    } catch (ApiException | Exception $e) {
        $this->logger->alert($e->getMessage());
        $this->logger->debug($e->getTraceAsString());
        return $response->withStatus(200);
    }
});

$app->run();
