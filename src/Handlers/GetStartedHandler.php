<?php
namespace HarassMapFbMessengerBot\Handlers;

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
        $userProfile = $this->messenger->getUserProfile($this->event->getSenderId());

        try {
            $this->dbConnection->insert('users', [
                'psid' => $this->event->getSenderId(),
                'first_name' => $userProfile->getFirstName(),
                'last_name' => $userProfile->getLastName(),
                'locale' => $userProfile->getLocale(),
                'timezone' => $userProfile->getTimezone(),
                'gender' => $userProfile->getGender(),
                'preferred_language' => $userProfile->getLocale() ?? self::LOCALE_DEFAULT,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            $this->dbConnection->update(
                'users',
                [
                    'first_name' => $userProfile->getFirstName(),
                    'last_name' => $userProfile->getLastName(),
                    'locale' => $userProfile->getLocale(),
                    'timezone' => $userProfile->getTimezone(),
                    'gender' => $userProfile->getGender(),
                ],
                [
                    'psid' => $this->event->getSenderId()
                ]
            );
        }

        $message = new Message('أساعدك ازاى؟');
        $message->setQuickReplies([
            new Text('الإبلاغ عن حالة تحرش', 'REPORT_INCIDENT'),
            new Text('عرض بلاغات التحرش', 'GET_INCIDENTS')
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }
}
