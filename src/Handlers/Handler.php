<?php
namespace HarassMapFbMessengerBot\Handlers;

use Doctrine\DBAL\Connection;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;

interface Handler
{
    public function __construct(Messenger $messenger, CallbackEvent $event, Connection $dbConnection);

    public function handle();
}
