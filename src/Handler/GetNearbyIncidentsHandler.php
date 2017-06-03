<?php
namespace HarassMapFbMessengerBot\Handler;

use HarassMapFbMessengerBot\User;
use HarassMapFbMessengerBot\Report;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\Button\WebUrl;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Model\QuickReply\Location;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use Tgallice\FBMessenger\Model\Attachment\Template\Button;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Interop\Container\ContainerInterface;
use DateTime;

class GetNearbyIncidentsHandler implements Handler
{
    private $messenger;

    private $event;

    private $user;

    protected $container;

    private $payload;

    public function __construct(
        ContainerInterface $container,
        CallbackEvent $event,
        User $user
    ) {
        $this->container = $container;
        $this->event = $event;
        $this->user = $user;
        $this->messenger = $this->container->messenger;
    }

    public function handle()
    {
        if (($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->payload = $this->event->getQuickReplyPayload(), 'GET_NEARBY_INCIDENTS'))
            || ($this->event instanceof PostbackEvent
            && 0 === mb_strpos($this->payload = $this->event->getPostbackPayload(), 'GET_NEARBY_INCIDENTS'))
        ) {
            $message = new Message(
                $this->container->translationService->getLocalizedString(
                    'can_you_please_share_your_location',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                )
            );
            $message->setQuickReplies([
                new Location(),
            ]);

            $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
        } elseif ($this->event instanceof MessageEvent
            && ! $this->event->isQuickReply() && $this->event->getMessage()->hasLocation()) {

            $webUrl = new WebUrl(
                $this->container->translationService->getLocalizedString(
                    'view_incidents_near_your_location',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'https://' . $_SERVER['HTTP_HOST'] . '/public/map.htm?lat=' . $this->event->getMessage()->getLatitude() . '&lng=' . $this->event->getMessage()->getLongitude()
            );
            $webUrl->setWebviewHeightRatio(WebUrl::HEIGHT_RATIO_FULL);

            $elements = [
                $webUrl,
            ];
            $message = new Button(
                $this->container->translationService->getLocalizedString(
                    'view',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                $elements
            );
            $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
        }
    }
}
