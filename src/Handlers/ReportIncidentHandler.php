<?php
namespace HarassMapFbMessengerBot\Handlers;

use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Callback\MessageEvent;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Tgallice\FBMessenger\Model\QuickReply\Location;
use Tgallice\FBMessenger\Model\Button\WebUrl;
use Tgallice\FBMessenger\Model\Attachment\Template\Button;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use DateTime;

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
        } elseif (! $this->event->isQuickReply() && $this->event->getMessage()->hasText()) {
            $this->saveDetails();
        } elseif (0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_DATE')) {
            $this->saveDate();
        } elseif (0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_TIME')) {
            $this->saveTime();
        } elseif (0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_HARASSMENT_TYPE')) {
            $this->saveHarassmentType();
        } elseif (0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_HARASSMENT_DETAILS')) {
            $this->saveHarassmentDetails();
        } elseif (0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_ASSISTANCE_OFFERED')) {
            $this->saveAssistanceOffered();
        } elseif (! $this->event->isQuickReply() && $this->event->getMessage()->hasLocation()) {
            $this->saveLocation();
        }
    }

    private function saveLocation()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $this->saveAnswerToReport(
            'latitude',
            $this->event->getMessage()->getLatitude(),
            $report
        );
        $this->saveAnswerToReport(
            'longitude',
            $this->event->getMessage()->getLongitude(),
            $report
        );

        $message = new Message('تم استلام البلاغ.');
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $message = new Message('نشكركم على التصرف بشكل إيجابي وعلى قيامكم بالإبلاغ عن التحرش الجنسي. تساعدنا بلاغاتكم على الحصول على أدلة بالغة الأهمية نستخدمها لإنشاء حملات توعية، وإجراء أبحاث جديدة، وتفعيل برنامجنا "مدارس وجامعات آمنة"، بالإضافة إلى تخطيط وتنفيذ أعمال مجتمعية عديدة في جميع أنحاء مصر من أجل القضاء على التقبل المجتمعي للتحرش والاعتداء الجنسي.');
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $message = new Message('للحصول على معلومات عن خدمات قانونية ونفسية مجانية تقدري تتصلي على نظرة للدراسات النسوية على تليفون 0227946992');
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $elements = [
            new WebUrl('نظرة للدراسات النسوية', 'http://nazra.org/%D8%A7%D8%AA%D8%B5%D9%84-%D8%A8%D9%86%D8%A7'),
            new WebUrl('خريطة التحرش', 'http://harassmap.org/ar/contact-us/'),
        ];
        $message = new Button('تواصل معنا للمساعدة:', $elements);
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
    }

    private function saveAssistanceOffered()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $assistenceOffered = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_ASSISTANCE_OFFERED_')
        );

        $this->saveAnswerToReport(
            'assistence_offered',
            $assistenceOffered === 'YES' ? 1 : 0,
            $report
        );

        $message = new Message('ممكن تبعتي مكان الحادثة باستخدام خاصية مشاركة المكان؟');
        $message->setQuickReplies([
            new Location(),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
    }

    private function saveHarassmentDetails()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $harassmentDetails = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_HARASSMENT_DETAILS_')
        );

        $this->saveAnswerToReport(
            'harassment_type_details',
            $harassmentDetails,
            $report
        );

        $message = new Message('هل قام المارة بالتدخل للمساعدة؟');
        $message->setQuickReplies([
            new Text('نعم', 'REPORT_INCIDENT_ASSISTANCE_OFFERED_YES'),
            new Text('لا', 'REPORT_INCIDENT_ASSISTANCE_OFFERED_NO'),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
    }

    private function saveHarassmentType()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $harassmentType = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_HARASSMENT_TYPE_')
        );

        $this->saveAnswerToReport(
            'harassment_type',
            $harassmentType,
            $report
        );

        $message = new Message('برجاء الاختيار:');
        switch ($harassmentType) {
            case 'VERBAL':
                $harassmentTypeDetails = [
                    new Text('النظر المتفحّص', 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL1'),
                    new Text('التلميحات بالوجه', 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL2'),
                    new Text('الندءات (البسبسة)', 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL3'),
                    new Text('التعليقات', 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL4'),
                    new Text('الملاحقة أو التتبع', 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL5'),
                    new Text('الدعوة الجنسة', 'REPORT_INCIDENT_HARASSMENT_DETAILS_VERBAL6'),
                ];
                break;

            case 'PHYSICAL':
                $harassmentTypeDetails = [
                    new Text('اللمس', 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL1'),
                    new Text('التعري', 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL2'),
                    new Text('التهديد والترهيب', 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL3'),
                    new Text('الاعتداء الجنسي', 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL4'),
                    new Text('الاغتصاب', 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL5'),
                    new Text('التحرش الجماعي', 'REPORT_INCIDENT_HARASSMENT_DETAILS_PHYSICAL6'),
                ];
                break;
        }
        $message->setQuickReplies($harassmentTypeDetails);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
    }

    private function saveTime()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $timeHour = mb_substr($this->event->getQuickReplyPayload(), mb_strlen('REPORT_INCIDENT_TIME_'));
        $time = new DateTime($timeHour . ':00');

        $this->saveAnswerToReport(
            'time',
            $time->format('H:i:s'),
            $report
        );

        $message = new Message('نوع التحرش؟');
        $message->setQuickReplies([
            new Text('لفظى', 'REPORT_INCIDENT_HARASSMENT_TYPE_VERBAL'),
            new Text('جسدى', 'REPORT_INCIDENT_HARASSMENT_TYPE_PHYSICAL'),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
    }

    private function saveDate()
    {
        $user = $this->getUserByPSID($this->event->getSenderId());
        $report = $this->getInProgressReportByUser($user['id']);

        $dateMessage = mb_substr($this->event->getQuickReplyPayload(), mb_strlen('REPORT_INCIDENT_DATE_'));
        switch ($dateMessage) {
            case 'TODAY':
                $date = new DateTime();
                $date = $date->format('Y-m-d');
                break;

            case 'YESTERDAY':
                $date = new DateTime();
                $date->setTimestamp(strtotime('yesterday'));
                $date = $date->format('Y-m-d');
                break;

            case '2_DAYS_AGO':
                $date = new DateTime();
                $date->setTimestamp(strtotime('2 days ago'));
                $date = $date->format('Y-m-d');
                break;

            case 'DATE_EARLIER':
            default:
                $date = new DateTime();
                $date->setTimestamp(strtotime('1 week ago'));
                $date = $date->format('Y-m-d');
                break;
        }

        $this->saveAnswerToReport(
            'date',
            $date,
            $report
        );

        $message = new Message('الساعة كام نقريبا؟');
        $message->setQuickReplies([
            new Text('12 الظهر', 'REPORT_INCIDENT_TIME_12'),
            new Text('3 العصر', 'REPORT_INCIDENT_TIME_15'),
            new Text('6 مساء', 'REPORT_INCIDENT_TIME_18'),
            new Text('12 نصف الليل', 'REPORT_INCIDENT_TIME_00'),
            new Text('6 الفجر', 'REPORT_INCIDENT_TIME_6'),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->advanceReportStep($report);
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

        $message = new Message('علاقتك بالبلاغ؟');
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
