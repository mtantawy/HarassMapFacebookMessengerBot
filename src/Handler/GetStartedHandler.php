<?php
namespace HarassMapFbMessengerBot\Handler;

use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\Button\WebUrl;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Model\Attachment\Template\Button;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Interop\Container\ContainerInterface;

class GetStartedHandler implements Handler
{
    const LOCALE_DEFAULT = 'ar_AR';

    private $messenger;

    private $event;

    private $dbConnection;

    protected $container;

    public function __construct(
        ContainerInterface $container,
        CallbackEvent $event
    ) {
        $this->container = $container;
        $this->event = $event;
        $this->messenger = $this->container->messenger;
        $this->dbConnection = $this->container->dbConnection;
    }

    public function handle()
    {
        $message = new Message('أساعدك ازاى؟');
        $message->setQuickReplies([
            new Text('الإبلاغ عن حالة تحرش', 'REPORT_INCIDENT'),
            new Text('عرض بلاغات التحرش', 'GET_INCIDENTS'),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }
}
