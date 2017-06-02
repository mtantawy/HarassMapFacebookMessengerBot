<?php

namespace HarassMapFbMessengerBot;

use HarassMapFbMessengerBot\User;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Interop\Container\ContainerInterface;

class ConversationRouter
{
    const HANDLER_GET_STARTED = 'get_started';
    const HANDLER_REPORT_INCIDENT = 'report_incident';
    const HANDLER_GET_INCIDENTS = 'get_reports';
    const HANDLER_CHANGE_LANGUAGE = 'change_language';

    protected $container;
       
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function route(CallbackEvent $event, User $user): string
    {
        $this->container->logger->debug(get_class($event));
        if ($this->isGetStarted($event, $user)) {
            $this->container->logger->debug(self::HANDLER_GET_STARTED);
            return self::HANDLER_GET_STARTED;
        } elseif ($this->isGetIncidents($event, $user)) {
            $this->container->logger->debug(self::HANDLER_GET_INCIDENTS);
            return self::HANDLER_GET_INCIDENTS;
        } elseif ($this->isChangeLanguage($event, $user)) {
            $this->container->logger->debug(self::HANDLER_CHANGE_LANGUAGE);
            return self::HANDLER_CHANGE_LANGUAGE;
        } elseif ($this->isReportIncident($event, $user)) {
            $this->container->logger->debug(self::HANDLER_REPORT_INCIDENT);
            return self::HANDLER_REPORT_INCIDENT;
        }

        return '';
    }

    private function isGetStarted(CallbackEvent $event, User $user): bool
    {
        return (
            ($event instanceof PostbackEvent && $event->getPostbackPayload() === 'GET_STARTED')
            || ($event instanceof MessageEvent
            && ! $event->isQuickReply()
            && $event->getMessage()->hasText()
            && ! $this->container->reportService->isUserOnReportDetailsStep($user->getId()))
        );
    }

    private function isReportIncident(CallbackEvent $event, User $user): bool
    {
        return (
            ($event instanceof PostbackEvent && 0 === mb_strpos($event->getPostbackPayload(), 'REPORT_INCIDENT'))
            || ($event instanceof MessageEvent
            && ! $event->isQuickReply()
            && $event->getMessage()->hasText()
            && $this->container->reportService->isUserOnReportDetailsStep($user->getId()))
            || ($event instanceof MessageEvent
            && $event->isQuickReply()
            && $event->getMessage()->hasText())
            || ($event instanceof MessageEvent
            && ! $event->isQuickReply()
            && $event->getMessage()->hasLocation())
        );
    }

    private function isGetIncidents(CallbackEvent $event, User $user): bool
    {
        return (
            ($event instanceof PostbackEvent && 0 === mb_strpos($event->getPostbackPayload(), 'GET_INCIDENTS'))
            || $event instanceof MessageEvent
            && $event->isQuickReply()
            && 0 === mb_strpos($event->getQuickReplyPayload(), 'GET_INCIDENTS')
        );
    }

    private function isChangeLanguage(CallbackEvent $event, User $user): bool
    {
        return (
            ($event instanceof PostbackEvent && 0 === mb_strpos($event->getPostbackPayload(), 'CHANGE_LANGUAGE'))
            || $event instanceof MessageEvent
            && $event->isQuickReply()
            && 0 === mb_strpos($event->getQuickReplyPayload(), 'CHANGE_LANGUAGE')
        );
    }
}
