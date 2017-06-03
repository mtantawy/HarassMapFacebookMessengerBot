<?php
namespace HarassMapFbMessengerBot\Handler;

use HarassMapFbMessengerBot\User;
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
    private $messenger;

    private $event;

    private $user;

    private $dbConnection;

    protected $container;

    public function __construct(
        ContainerInterface $container,
        CallbackEvent $event,
        User $user
    ) {
        $this->container = $container;
        $this->event = $event;
        $this->user = $user;
        $this->messenger = $this->container->messenger;
        $this->dbConnection = $this->container->dbConnection;
    }

    public function handle()
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'welcome_message',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'how_can_i_help_you',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $message->setQuickReplies([
            new Text(
                $this->container->translationService->getLocalizedString(
                    'view_harassment_incidents',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'GET_INCIDENTS'
            ),
            new Text(
                $this->container->translationService->getLocalizedString(
                    'view_nearby_harassment_incidents',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'GET_NEARBY_INCIDENTS'
            ),
            new Text(
                $this->container->translationService->getLocalizedString(
                    'report_harassment_incident',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT'
            ),
            new Text(
                $this->container->translationService->getLocalizedString(
                    'change_language',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'CHANGE_LANGUAGE'
            ),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }
}
