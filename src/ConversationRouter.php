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

    protected $container;
       
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function route(CallbackEvent $event, User $user): string
    {
        if ($this->isGetStarted($event, $user)) {
            $this->container->logger->debug(self::HANDLER_GET_STARTED);
            return self::HANDLER_GET_STARTED;
        } elseif ($this->isReportIncident($event)) {
            $this->container->logger->debug(self::HANDLER_REPORT_INCIDENT);
            return self::HANDLER_REPORT_INCIDENT;
        } elseif ($this->isGetIncidents($event)) {
            $this->container->logger->debug(self::HANDLER_GET_INCIDENTS);
            return self::HANDLER_GET_INCIDENTS;
        }
    }

    private function isGetStarted(CallbackEvent $event, User $user): bool
    {
        return (($event instanceof PostbackEvent && $event->getPostbackPayload() === 'GET_STARTED')
            || ($event instanceof MessageEvent
            && ! $event->isQuickReply()
            && $event->getMessage()->hasText()
            && ! $this->container->reportService->isUserOnReportDetailsStep($user->getId()))
        );
    }

    private function isReportIncident(CallbackEvent $event): bool
    {
        return ($event instanceof MessageEvent
            && 0 !== mb_strpos($event->getQuickReplyPayload(), 'GET_INCIDENTS')
        );
    }

    private function isGetIncidents(CallbackEvent $event): bool
    {
        return ($event instanceof MessageEvent
            && $event->isQuickReply()
            && 0 === mb_strpos($event->getQuickReplyPayload(), 'GET_INCIDENTS')
        );
    }
}
