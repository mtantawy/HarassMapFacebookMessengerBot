<?php
namespace HarassMapFbMessengerBot\Handlers;

use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Doctrine\DBAL\Connection;

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

        $this->dbConnection->insert('users', [
            'psid' => $this->event->getSenderId(),
            'first_name' => $userProfile->getFirstName(),
            'last_name' => $userProfile->getLastName(),
            'locale' => $userProfile->getLocale(),
            'timezone' => $userProfile->getTimezone(),
            'gender' => $userProfile->getGender(),
            'preferred_language' => $userProfile->getLocale() ?? self::LOCALE_DEFAULT,
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), 'Got your profile ;)');
    }
}
