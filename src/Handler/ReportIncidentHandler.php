<?php
namespace HarassMapFbMessengerBot\Handler;

use HarassMapFbMessengerBot\Report;
use HarassMapFbMessengerBot\Service\ReportService;
use HarassMapFbMessengerBot\Service\UserService;
use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Callback\MessageEvent;
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

    private $dbConnection;

    private $userService;

    private $reportService;

    protected $container;

    public function __construct(
        ContainerInterface $container,
        CallbackEvent $event
    ) {
        $this->container = $container;
        $this->event = $event;
        $this->messenger = $this->container->messenger;
        $this->dbConnection = $this->container->dbConnection;
        $this->userService = $this->container->userService;
        $this->reportService = $this->container->reportService;
    }

    public function handle()
    {
        if ($this->event instanceof MessageEvent
            && $this->event->getQuickReplyPayload() === 'REPORT_INCIDENT') {
            $this->startReport();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_RELATIONSHIP')) {
            $this->saveRelationship();
        } elseif ($this->event instanceof MessageEvent
            && ! $this->event->isQuickReply() && $this->event->getMessage()->hasText()) {
            $this->saveDetails();
        } elseif (0 === mb_strpos($this->event->getPostbackPayload(), 'REPORT_INCIDENT_DATETIME')) {
            $this->saveDateTime();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_HARASSMENT_TYPE')) {
            $this->saveHarassmentType();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_HARASSMENT_DETAILS')) {
            $this->saveHarassmentDetails();
        } elseif ($this->event instanceof MessageEvent
            && 0 === mb_strpos($this->event->getQuickReplyPayload(), 'REPORT_INCIDENT_ASSISTANCE_OFFERED')) {
            $this->saveAssistanceOffered();
        } elseif ($this->event instanceof MessageEvent
            && ! $this->event->isQuickReply() && $this->event->getMessage()->hasLocation()) {
            $this->saveLocation();
        }
    }

    private function saveLocation()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        $this->reportService->saveAnswerToReport(
            'latitude',
            $this->event->getMessage()->getLatitude(),
            $report
        );
        $this->reportService->saveAnswerToReport(
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

        $this->reportService->advanceReportStep($report);
    }

    private function saveAssistanceOffered()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        $assistenceOffered = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_ASSISTANCE_OFFERED_')
        );

        $this->reportService->saveAnswerToReport(
            'assistence_offered',
            $assistenceOffered === 'YES' ? 1 : 0,
            $report
        );

        $message = new Message('ممكن تبعتي مكان الحادثة باستخدام خاصية مشاركة المكان؟');
        $message->setQuickReplies([
            new Location(),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->reportService->advanceReportStep($report);
    }

    private function saveHarassmentDetails()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        $harassmentDetails = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_HARASSMENT_DETAILS_')
        );

        $this->reportService->saveAnswerToReport(
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

        $this->reportService->advanceReportStep($report);
    }

    private function saveHarassmentType()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        $harassmentType = mb_substr(
            $this->event->getQuickReplyPayload(),
            mb_strlen('REPORT_INCIDENT_HARASSMENT_TYPE_')
        );

        $this->reportService->saveAnswerToReport(
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

        $this->reportService->advanceReportStep($report);
    }

    private function saveTime()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        $timeHour = mb_substr($this->event->getQuickReplyPayload(), mb_strlen('REPORT_INCIDENT_TIME_'));
        $time = new DateTime($timeHour . ':00');

        $this->reportService->saveAnswerToReport(
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

        $this->reportService->advanceReportStep($report);
    }

    private function saveDateTime()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        if ($this->event->getPostbackPayload() === 'REPORT_INCIDENT_DATETIME_NOW') {
            $datetime = new DateTime();
            $datetime = $datetime->format('Y-m-d H:i:s');
        }

        $this->reportService->saveAnswerToReport(
            'datetime',
            $datetime,
            $report
        );

        $message = new Message('نوع التحرش؟');
        $message->setQuickReplies([
            new Text('لفظى', 'REPORT_INCIDENT_HARASSMENT_TYPE_VERBAL'),
            new Text('جسدى', 'REPORT_INCIDENT_HARASSMENT_TYPE_PHYSICAL'),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->reportService->advanceReportStep($report);
    }

    private function saveDetails()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        $this->reportService->saveAnswerToReport(
            'details',
            $this->event->getMessageText(),
            $report
        );

        $elements = [
            new Postback('دلوقتى', 'REPORT_INCIDENT_DATETIME_NOW'),
            new WebUrl('إدخل الوقت و التاريخ', 'https://v2.hmfbbot.mtantawy.com/datetimepicker.htm?ids=' . json_encode(['user_id' => $user->getId(), 'report_id' => $report->getId()])),
        ];
        $message = new Button('امتى حصل التحرش؟', $elements);
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->reportService->advanceReportStep($report);
    }

    private function saveRelationship()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());
        $report = $this->reportService->getInProgressReportByUser($user->getId());

        $this->reportService->saveAnswerToReport(
            'relation',
            mb_substr($this->event->getQuickReplyPayload(), mb_strlen('REPORT_INCIDENT_RELATIONSHIP_')),
            $report
        );

        $message = new Message('من فضلك، أبلغنا عن الواقعة بأكثر قدر ممكن من التفاصيل فى رسالة واحدة.');

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->reportService->advanceReportStep($report);
    }

    private function startReport()
    {
        $user = $this->userService->getUserByFacebookPSID($this->event->getSenderId());

        $this->reportService->startReportForUser($user->getId());

        $response = $this->messenger->sendMessage($this->event->getSenderId(), 'تقدري تبلغي عن حادثة التحرش هنا بسرية تامه.  مش هنحتفظ بأي بيانات او معلومات شخصية ليكي.');

        $message = new Message('علاقتك بالبلاغ؟');
        $message->setQuickReplies([
            new Text('حصلي شخصيا', 'REPORT_INCIDENT_RELATIONSHIP_PERSONAL'),
            new Text('شاهد عليه', 'REPORT_INCIDENT_RELATIONSHIP_WITNESS')
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $this->reportService->advanceReportStep($this->reportService->getInProgressReportByUser($user->getId()));
    }
}
