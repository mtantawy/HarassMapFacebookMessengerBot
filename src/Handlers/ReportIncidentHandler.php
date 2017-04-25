<?php
namespace HarassMapFbMessengerBot\Handlers;

use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class ReportIncidentHandler implements Handler
{
    private $messenger;

    private $event;

    private $dbConnection;

    private $steps = [
        'init',
        'relation',
        'details',
        'date',
        'time',
        'harassment_type',
        'harassment_type_details',
        'assistance_offered',
        'location',
        'done'
    ];

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
        if ($this->event->getQuickReplyPayload() === 'REPORT_INCIDENT') {
            $this->startReport();
        } elseif (0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_RELATIONSHIP')) {
            $this->saveRelationship();
        } elseif (! $this->event->isQuickReply()) {
            $this->saveDetails();
        }
    }

    private function saveDetails()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $this->saveAnswerToReport(
            'details',
            $this->event->getMessageText(),
            $report
        );

        $message = new Message('امتى حصل التحرش؟');
        $message->setQuickReplies([
            new Text('النهارده', 'REPORT_INCIDENT_DATE_TODAY'),
            new Text('امبارح', 'REPORT_INCIDENT_DATE_YESTERDAY'),
            new Text('اول امبارح', 'REPORT_INCIDENT_DATE_DAY_BEFORE_YESTERDAY'),
            new Text('قبل كده', 'REPORT_INCIDENT_DATE_EARLIER'),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
    }

    private function saveRelationship()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $this->saveAnswerToReport(
            'relation',
            mb_substr($this->event->getQuickReplyPayload(), mb_strlen('REPORT_INCIDENT_RELATIONSHIP_')),
            $report
        );

        $message = new Message('من فضلك، أبلغنا عن الواقعة بأكثر قدر ممكن من التفاصيل فى رسالة واحدة.');

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
    }

    private function startReport()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());

        $this->dbConnection->insert('reports', [
            'user_id' => $user['id'],
            'step' => reset($this->steps),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), 'تقدري تبلغي عن حادثة التحرش هنا بسرية تامه.  مش هنحتفظ باي بيانات او معلومات شخصية ليكي.');

        $message = new Message('علاقتك بالبلاغ؟:');
        $message->setQuickReplies([
            new Text('حصلي شخصيا', 'REPORT_INCIDENT_RELATIONSHIP_PERSONAL'),
            new Text('شاهد عليه', 'REPORT_INCIDENT_RELATIONSHIP_WITNESS')
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($this->getInProgressReportByUser($user['id']));
    }

    private function getUserByPSID(string $psid): array
    {
        return $this->dbConnection->fetchAssoc(
            'SELECT * FROM `users` WHERE `psid` = ?',
            [$psid]
        );
    }

    private function getInProgressReportByUser(int $id): array
    {
        $doneStatus = $this->steps[count($this->steps) - 1];
        return $this->dbConnection->fetchAssoc(
            'SELECT * FROM `reports` WHERE `user_id` = ? AND `step` != "' . $doneStatus . '" order by updated_at DESC limit 1',
            [$id]
        );
    }

    private function advanceReportStep(array $report)
    {
        $this->dbConnection->update(
            'reports',
            [
                'step' => $this->steps[array_search($report['step'], $this->steps) + 1]
            ],
            [
                'id' => $report['id']
            ]
        );
    }

    private function saveAnswerToReport(string $field, string $answer, array $report)
    {
        $this->dbConnection->update(
            'reports',
            [
                $field => $answer
            ],
            [
                'id' => $report['id']
            ]
        );
    }
}
