<?php
namespace HarassMapFbMessengerBot\Handler;

use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class GetStartedHandler implements Handler
{
    const LOCALE_DEFAULT = 'ar_AR';

    private $messenger;

    private $event;

    private $dbConnection;

    public function __construct(
        Messenger $messenger,
        CallbackEvent $event,
        Connection $dbConnection
    ) {
        $this->messenger = $messenger;
        $this->event = $event;
        $this->dbConnection = $dbConnection;
    }

    public function handle()
    {
        $message = new Message('أساعدك ازاى؟');
        $message->setQuickReplies([
            new Text('الإبلاغ عن حالة تحرش', 'REPORT_INCIDENT'),
            new Text('عرض بلاغات التحرش', 'GET_INCIDENTS')
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }
}
