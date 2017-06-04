<?php

namespace HarassMapFbMessengerBot\Controller;

use Interop\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\QuickReply\Text;

class ReportController
{
    protected $container;
   
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
   
    public function __invoke(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $ids = $request->getParsedBody()['ids'];
        $ids = json_decode($ids, true);
        $datetime = $request->getParsedBody()['datetime'];

        if ($this->container->reportService->setDatetimeForUserReport(
            $ids['user_id'],
            $ids['report_id'],
            $datetime
        )) {
            $message = new Message(
                $this->container->translationService->getLocalizedString(
                    'harassment_type',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                )
            );
            $message->setQuickReplies([
                new Text(
                    $this->container->translationService->getLocalizedString(
                        'verbal',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ),
                    'REPORT_INCIDENT_HARASSMENT_TYPE_VERBAL'
                ),
                new Text(
                    $this->container->translationService->getLocalizedString(
                        'physical',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ),
                    'REPORT_INCIDENT_HARASSMENT_TYPE_PHYSICAL'
                )
            ]);

            $this->container->messenger->sendMessage(
                $this->container->userService->getById($ids['user_id'])->getPsid(),
                $message
            );

            $response->getBody()->write(
                '<script>window.location.href = "https://www.messenger.com/closeWindow/?image_url=http://harassmap.org/en/wp-content/uploads/2015/03/en1-150x150.png&display_text=Thanks";</script>'
            );
            return $response;
        } else {
            // show error msg and prompt to choose date/time again
        }
    }
}
