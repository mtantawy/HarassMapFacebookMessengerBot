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
            $report = $this->container->reportService->getInProgressReportByUser($ids['user_id']);
            $user = $this->container->userService->getById($ids['user_id']);
            $this->container->reportService->advanceReportStep($report);

            $message = new Message(
                $this->container->translationService->getLocalizedString(
                    'harassment_type',
                    $user->getPreferredLanguage(),
                    $user->getGender()
                )
            );
            $message->setQuickReplies([
                new Text(
                    $this->container->translationService->getLocalizedString(
                        'verbal',
                        $user->getPreferredLanguage(),
                        $user->getGender()
                    ),
                    'REPORT_INCIDENT_HARASSMENT_TYPE_VERBAL'
                ),
                new Text(
                    $this->container->translationService->getLocalizedString(
                        'physical',
                        $user->getPreferredLanguage(),
                        $user->getGender()
                    ),
                    'REPORT_INCIDENT_HARASSMENT_TYPE_PHYSICAL'
                )
            ]);

            $this->container->messenger->sendMessage(
                $user->getPsid(),
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
