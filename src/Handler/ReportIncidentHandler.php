<?php
namespace HarassMapFbMessengerBot\Handler;

use HarassMapFbMessengerBot\User;
use HarassMapFbMessengerBot\Report;
use HarassMapFbMessengerBot\Service\ReportService;
use HarassMapFbMessengerBot\Service\UserService;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Model\QuickReply\Location;
use Tgallice\FBMessenger\Model\Button\WebUrl;
use Tgallice\FBMessenger\Model\Button\Postback;
use Tgallice\FBMessenger\Model\Attachment\Template\Button;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Interop\Container\ContainerInterface;
use DateTime;
use Exception;

class ReportIncidentHandler implements Handler
{
    private $messenger;

    private $event;

    private $user;

    private $report;

    private $dbConnection;

    private $userService;

    private $reportService;

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
        $this->userService = $this->container->userService;
        $this->reportService = $this->container->reportService;

        try {
            $this->report = $this->reportService->getInProgressReportByUser($user->getId());
        } catch (Exception $e) {
            //
        }
    }

    public function handle()
    {
        if (($this->event instanceof MessageEvent
            && $this->event->getQuickReplyPayload() === 'REPORT_INCIDENT')
            || ($this->event instanceof PostbackEvent
            && $this->event->getPostbackPayload() === 'REPORT_INCIDENT')) {
            if ($this->canStartReport()) {
                $this->startReport();
                $this->triggerRelation();
            } else {
                $this->offerToRestartOrResumeReport();
            }
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_ACTION')) {
            $this->restartOrResumeReport();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_RELATIONSHIP')) {
            $this->saveRelationship();
            $this->triggerDetails();
        } elseif ($this->event instanceof MessageEvent
            && ! $this->event->isQuickReply() && $this->event->getMessage()->hasText()) {
            $this->saveDetails();
            $this->triggerDateTime();
        } elseif ($this->event instanceof PostbackEvent
            && 0 === mb_strpos($this->event->getPostbackPayload(), 'REPORT_INCIDENT_DATETIME')) {
            $this->saveDateTime();
            $this->triggerHarassmentType();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_HARASSMENT_TYPE')) {
            $this->saveHarassmentType();
            $harassmentType = mb_substr(
                $this->event->getQuickReplyPayload(),
                mb_strlen('REPORT_INCIDENT_HARASSMENT_TYPE_')
            );
            $this->triggerHarassmentTypeDetails($harassmentType);
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_HARASSMENT_DETAILS')) {
            $this->saveHarassmentTypeDetails();
            $this->triggerAssistanceOffered();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_ASSISTANCE_OFFERED')) {
            $this->saveAssistanceOffered();
            $this->triggerLocation();
        } elseif ($this->event instanceof MessageEvent
            && ! $this->event->isQuickReply() && $this->event->getMessage()->hasLocation()) {
            $this->saveLocation();
        }
    }

    private function restartOrResumeReport()
    {
        $action = mb_substr($this->event->getQuickReplyPayload(), mb_strlen('REPORT_INCIDENT_ACTION_'));

        switch ($action) {
            case 'RESUME':
                if ($this->canStartReport()) {
                     $this->startReport();
                    $this->triggerRelation();
                }
                $step = $this->report->getStep();
                $pascalCaseStep = str_replace(' ', '', ucwords(str_replace('_', ' ', $step)));
                $stepMethod = 'trigger' . $pascalCaseStep;
                $this->$stepMethod();
                break;

            case 'NEW':
            default:
                $this->reportService->deleteNotDoneReportsForUser($this->user->getId());
                $this->startReport();
                $this->triggerRelation();
                break;
        }
    }

    private function canStartReport(): bool
    {
        return (is_null($this->report) && ! $this->report instanceof Report);
    }

    private function offerToRestartOrResumeReport()
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'resume_or_new_report',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $message->setQuickReplies([
            new Text(
                $this->container->translationService->getLocalizedString(
                    'resume',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT_ACTION_RESUME'
            ),
            new Text(
                $this->container->translationService->getLocalizedString(
                    'new',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT_ACTION_NEW'
            )
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function saveLocation()
    {
        $this->reportService->saveAnswerToReport(
            'latitude',
            $this->event->getMessage()->getLatitude(),
            $this->report
        );
        $this->reportService->saveAnswerToReport(
            'longitude',
            $this->event->getMessage()->getLongitude(),
            $this->report
        );

        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'report_received',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'thanks_for_reporting',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'get_help_from_nazra',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $elements = [
            new WebUrl(
                $this->container->translationService->getLocalizedString(
                    'nazra_for_feminist_studies',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'http://nazra.org/%D8%A7%D8%AA%D8%B5%D9%84-%D8%A8%D9%86%D8%A7'
            ),
            new WebUrl(
                $this->container->translationService->getLocalizedString(
                    'harassmap',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'http://harassmap.org/ar/contact-us/'
            ),
        ];
        $message = new Button(
            $this->container->translationService->getLocalizedString(
                'contact_us_for_help',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            ),
            $elements
        );
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->reportService->advanceReportStep($this->report);
    }

    private function triggerLocation()
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'can_you_please_share_incident_location',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $message->setQuickReplies([
            new Location(),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function saveAssistanceOffered()
    {
        $assistenceOffered = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_ASSISTANCE_OFFERED_')
        );

        $this->reportService->saveAnswerToReport(
            'assistence_offered',
            $assistenceOffered === 'YES' ? 1 : 0,
            $this->report
        );

        $this->reportService->advanceReportStep($this->report);
    }

    private function saveHarassmentTypeDetails()
    {
        $harassmentDetails = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_HARASSMENT_DETAILS_')
        );

        $this->reportService->saveAnswerToReport(
            'harassment_type_details',
            $harassmentDetails,
            $this->report
        );

        $this->reportService->advanceReportStep($this->report);
    }

    private function triggerAssistanceOffered()
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'did_anyone_offer_help',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $message->setQuickReplies([
            new Text(
                $this->container->translationService->getLocalizedString(
                    'yes',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT_ASSISTANCE_OFFERED_YES'
            ),
            new Text(
                $this->container->translationService->getLocalizedString(
                    'no',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT_ASSISTANCE_OFFERED_NO'
            )
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function saveHarassmentType()
    {
        $harassmentType = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_HARASSMENT_TYPE_')
        );

        $this->reportService->saveAnswerToReport(
            'harassment_type',
            $harassmentType,
            $this->report
        );

        $this->reportService->advanceReportStep($this->report);
    }

    private function triggerHarassmentTypeDetails(string $harassmentType)
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'please_choose',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        switch ($harassmentType) {
            case 'VERBAL':
                $harassmentTypeDetails = [
                    new Text(Report::HARASSMENT_TYPES['verbal'][1], 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL1'),
                    new Text(Report::HARASSMENT_TYPES['verbal'][2], 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL2'),
                    new Text(Report::HARASSMENT_TYPES['verbal'][3], 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL3'),
                    new Text(Report::HARASSMENT_TYPES['verbal'][4], 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL4'),
                    new Text(Report::HARASSMENT_TYPES['verbal'][5], 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL5'),
                    new Text(Report::HARASSMENT_TYPES['verbal'][6], 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL6'),
                ];
                break;

            case 'PHYSICAL':
                $harassmentTypeDetails = [
                    new Text(Report::HARASSMENT_TYPES['physical'][1], 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL1'),
                    new Text(Report::HARASSMENT_TYPES['physical'][2], 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL2'),
                    new Text(Report::HARASSMENT_TYPES['physical'][3], 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL3'),
                    new Text(Report::HARASSMENT_TYPES['physical'][4], 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL4'),
                    new Text(Report::HARASSMENT_TYPES['physical'][5], 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL5'),
                    new Text(Report::HARASSMENT_TYPES['physical'][6], 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL6'),
                ];
                break;
        }
        $message->setQuickReplies($harassmentTypeDetails);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function saveDateTime()
    {
        if ($this->event->getPostbackPayload() === 'REPORT_INCIDENT_DATETIME_NOW') {
            $datetime = new DateTime();
            $datetime = $datetime->format('Y-m-d H:i:s');
        }

        $this->reportService->saveAnswerToReport(
            'datetime',
            $datetime,
            $this->report
        );

        $this->reportService->advanceReportStep($this->report);
    }

    private function triggerHarassmentType()
    {
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

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function saveDetails()
    {
        $this->reportService->saveAnswerToReport(
            'details',
            $this->event->getMessageText(),
            $this->report
        );

        $this->reportService->advanceReportStep($this->report);
    }

    private function triggerDateTime()
    {
        $webUrl = new WebUrl(
            $this->container->translationService->getLocalizedString(
                'enter_date_and_time',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            ),
            'https://' . $_SERVER['HTTP_HOST'] . '/public/datetimepicker.htm?ids=' . json_encode(['user_id' => $this->user->getId(), 'report_id' => $this->report->getId()])
        );
        $webUrl->setWebviewHeightRatio(WebUrl::HEIGHT_RATIO_TALL);

        $elements = [
            new Postback(
                $this->container->translationService->getLocalizedString(
                    'now',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT_DATETIME_NOW'
            ),
            $webUrl,
        ];
        $message = new Button(
            $this->container->translationService->getLocalizedString(
                'when_did_incident_happen',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            ),
            $elements
        );
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function saveRelationship()
    {
        $this->reportService->saveAnswerToReport(
            'relation',
            mb_substr($this->event->getQuickReplyPayload(), mb_strlen('REPORT_INCIDENT_RELATIONSHIP_')),
            $this->report
        );

        $this->reportService->advanceReportStep($this->report);
    }

    private function triggerDetails()
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'please_explain_incident_in_one_message',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function triggerRelation()
    {
        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'relationship',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $message->setQuickReplies([
            new Text(
                $this->container->translationService->getLocalizedString(
                    'relationship_personal',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT_RELATIONSHIP_PERSONAL'
            ),
            new Text(
                $this->container->translationService->getLocalizedString(
                    'relationship_witness',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'REPORT_INCIDENT_RELATIONSHIP_WITNESS'
            )
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function startReport()
    {
        $this->reportService->startReportForUser($this->user->getId());
        $this->report = $this->reportService->getInProgressReportByUser($this->user->getId());

        $response = $this->messenger->sendMessage(
            $this->event->getSenderId(),
            $this->container->translationService->getLocalizedString(
                'here_you_report_incident_privately_we_donot_store_personal_info',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );

        $this->reportService->advanceReportStep($this->report);
    }

    private function triggerInit()
    {
        $this->triggerRelation();
    }
}
