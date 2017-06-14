<?php
namespace HarassMapFbMessengerBot\Handler;

use HarassMapFbMessengerBot\User;
use HarassMapFbMessengerBot\Report;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Callback\PostbackEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Interop\Container\ContainerInterface;
use DateTime;

class GetIncidentsHandler implements Handler
{
    private $messenger;

    private $event;

    private $user;

    private $dbConnection;

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
        $this->dbConnection = $this->container->dbConnection;
    }

    public function handle()
    {
        if (($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->payload = $this->event->getQuickReplyPayload(), 'GET_INCIDENTS'))
            || ($this->event instanceof PostbackEvent
            && 0 === mb_strpos($this->payload = $this->event->getPostbackPayload(), 'GET_INCIDENTS'))
        ) {
            $this->getOneReportByOffset();
        }
    }

    private function getOneReportByOffset()
    {
        $offset = 0;
        if (mb_strlen($this->payload) > mb_strlen('GET_INCIDENTS_OFFSET_')) {
            $offset = (int) mb_substr($this->payload, mb_strlen('GET_INCIDENTS_OFFSET_'));
        }

        $report = $this->getReportByOffset($offset);

        if (! empty($report)) {
            $report = $this->prepareReport($report);
        } else {
            $message = new Message(
                $this->container->translationService->getLocalizedString(
                    'no_more_reports',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                )
            );
            $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
            return;
        }

        $message = new Message($report);
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $message = new Message(
            $this->container->translationService->getLocalizedString(
                'get_more_reports',
                $this->user->getPreferredLanguage(),
                $this->user->getGender()
            )
        );
        $message->setQuickReplies([
            new Text(
                $this->container->translationService->getLocalizedString(
                    'next',
                    $this->user->getPreferredLanguage(),
                    $this->user->getGender()
                ),
                'GET_INCIDENTS_OFFSET_' . ($offset + 1)
            ),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function prepareReport(array $report): string
    {
        $preparedReport = '';
        foreach ($report as $key => $value) {
            switch ($key) {
                case 'created_at':
                    $preparedReport .= PHP_EOL . $this->container->translationService->getLocalizedString(
                        'report_datetime',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ) . ': ' . $value;
                    break;

                case 'relation':
                    switch ($value) {
                        case 'PERSONAL':
                            $relation = $this->container->translationService->getLocalizedString(
                                'relationship_personal',
                                $this->user->getPreferredLanguage(),
                                $this->user->getGender()
                            );
                            break;

                        case 'WITNESS':
                            $relation = $this->container->translationService->getLocalizedString(
                                'relationship_witness',
                                $this->user->getPreferredLanguage(),
                                $this->user->getGender()
                            );
                            break;
                        
                        default:
                            $relation = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . $this->container->translationService->getLocalizedString(
                        'relationship_reporting',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ) . ': ' . $relation;
                    break;

                case 'details':
                    $preparedReport .= PHP_EOL . $this->container->translationService->getLocalizedString(
                        'details',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ) . ': ' . mb_substr($value, 0, 350) . (mb_strlen($value) > 350 ? '...' : '');
                    break;

                case 'datetime':
                    $preparedReport .= PHP_EOL . $this->container->translationService->getLocalizedString(
                        'incident_datetime',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ) . ': ' . $value;
                    break;

                case 'harassment_type':
                    switch ($value) {
                        case 'VERBAL':
                            $harassmentType = $this->container->translationService->getLocalizedString(
                                'verbal',
                                $this->user->getPreferredLanguage(),
                                $this->user->getGender()
                            );
                            break;

                        case 'PHYSICAL':
                            $harassmentType = $this->container->translationService->getLocalizedString(
                                'physical',
                                $this->user->getPreferredLanguage(),
                                $this->user->getGender()
                            );
                            break;
                        
                        default:
                            $harassmentType = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . $harassmentType = $this->container->translationService->getLocalizedString(
                        'harassment_type',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ) . ': ' . $harassmentType;
                    break;

                case 'harassment_type_details':
                    switch ($value) {
                        case 'VERBAL1':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['verbal'][1]);
                            break;

                        case 'VERBAL2':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['verbal'][2]);
                            break;

                        case 'VERBAL3':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['verbal'][3]);
                            break;

                        case 'VERBAL4':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['verbal'][4]);
                            break;

                        case 'VERBAL5':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['verbal'][5]);
                            break;

                        case 'VERBAL6':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['verbal'][6]);
                            break;

                        case 'PHYSICAL1':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['physical'][1]);
                            break;

                        case 'PHYSICAL2':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['physical'][2]);
                            break;

                        case 'PHYSICAL3':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['physical'][3]);
                            break;

                        case 'PHYSICAL4':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['physical'][4]);
                            break;

                        case 'PHYSICAL5':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['physical'][5]);
                            break;

                        case 'PHYSICAL6':
                            $harassmentTypeDetails = $this->translate(Report::HARASSMENT_TYPES['physical'][6]);
                            break;
                        
                        default:
                            $harassmentTypeDetails = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . $this->container->translationService->getLocalizedString(
                        'more_details',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ) . ': ' . $harassmentTypeDetails;
                    break;

                case 'assistence_offered':
                    switch ($value) {
                        case '1':
                            $assistenceOffered = $this->container->translationService->getLocalizedString(
                                'yes',
                                $this->user->getPreferredLanguage(),
                                $this->user->getGender()
                            );
                            break;

                        case '0':
                            $assistenceOffered = $this->container->translationService->getLocalizedString(
                                'no',
                                $this->user->getPreferredLanguage(),
                                $this->user->getGender()
                            );
                            break;
                        
                        default:
                            $assistenceOffered = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . $this->container->translationService->getLocalizedString(
                        'did_anyone_offer_help',
                        $this->user->getPreferredLanguage(),
                        $this->user->getGender()
                    ) . ' ' . $assistenceOffered;
                    break;
                
                default:
                    break;
            }
        }

        return $preparedReport;
    }

    private function getReportByOffset(int $offset = 0): array
    {
        $report = $this->dbConnection->fetchAssoc(
            'SELECT * FROM `reports` WHERE `step` = "done" order by updated_at ASC limit 1 offset ?',
            [$offset],
            ['integer']
        );

        return is_array($report) ? $report : [];
    }

    private function translate(string $string): string
    {
        return $this->container->translationService->getLocalizedString(
            $string,
            $this->user->getPreferredLanguage(),
            $this->user->getGender()
        );
    }
}
