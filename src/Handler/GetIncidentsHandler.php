<?php
namespace HarassMapFbMessengerBot\Handler;

use Tgallice\FBMessenger\Messenger;
use Tgallice\FBMessenger\Callback\CallbackEvent;
use Tgallice\FBMessenger\Model\Message;
use Tgallice\FBMessenger\Model\QuickReply\Text;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Interop\Container\ContainerInterface;
use DateTime;

class GetIncidentsHandler implements Handler
{
    private $messenger;

    private $event;

    private $dbConnection;

    protected $container;

    public function __construct(
        ContainerInterface $container,
        CallbackEvent $event
    ) {
        $this->container = $container;
        $this->event = $event;
        $this->messenger = $this->container->messenger;
        $this->dbConnection = $this->container->dbConnection;
    }

    public function handle()
    {
        if (0 === mb_strpos($this->event->getQuickReplyPayload(), 'GET_INCIDENTS')) {
            $this->getOneReportByOffset();
        }
    }

    private function getOneReportByOffset()
    {
        $offset = 0;
        if (mb_strlen($this->event->getQuickReplyPayload()) > mb_strlen('GET_INCIDENTS_OFFSET_')) {
            $offset = (int) mb_substr($this->event->getQuickReplyPayload(), mb_strlen('GET_INCIDENTS_OFFSET_'));
        }

        $report = $this->getReportByOffset($offset);

        if (! empty($report)) {
            $report = $this->prepareReport($report);
        } else {
            $message = new Message('لا يوجد المزيد من التقارير');
            $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
            return;
        }

        $message = new Message($report);
        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);

        $message = new Message('المزيد من التقارير:');
        $message->setQuickReplies([
            new Text('التالى', 'GET_INCIDENTS_OFFSET_' . ($offset + 1)),
        ]);

        $response = $this->messenger->sendMessage($this->event->getSenderId(), $message);
    }

    private function prepareReport(array $report): string
    {
        $preparedReport = '';
        foreach ($report as $key => $value) {
            switch ($key) {
                case 'created_at':
                    $preparedReport .= PHP_EOL . 'تاريخ التقرير: ' . $value;
                    break;

                case 'relation':
                    switch ($value) {
                        case 'PERSONAL':
                            $relation = 'نفسه';
                            break;

                        case 'WITNESS':
                            $relation = 'شاهد';
                            break;
                        
                        default:
                            $relation = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . 'علاقة مرسل التقرير بالحادثة: ' . $relation;
                    break;

                case 'details':
                    $preparedReport .= PHP_EOL . 'التفاصيل: ' . $value;
                    break;

                case 'date':
                    $preparedReport .= PHP_EOL . 'تاريخ الحادثة: ' . $value;
                    break;

                case 'time':
                    $preparedReport .= PHP_EOL . 'وقت الحادثة: ' . $value;
                    break;

                case 'harassment_type':
                    switch ($value) {
                        case 'VERBAL':
                            $harassmentType = 'لفظى';
                            break;

                        case 'PHYSICAL':
                            $harassmentType = 'جسدى';
                            break;
                        
                        default:
                            $harassmentType = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . 'نوع التحرش: ' . $harassmentType;
                    break;

                case 'harassment_type_details':
                    switch ($value) {
                        case 'VERBAL1':
                            $harassmentTypeDetails = 'النظر المتفحّص';
                            break;

                        case 'VERBAL2':
                            $harassmentTypeDetails = 'التلميحات بالوجه';
                            break;

                        case 'VERBAL3':
                            $harassmentTypeDetails = 'الندءات (البسبسة)';
                            break;

                        case 'VERBAL4':
                            $harassmentTypeDetails = 'التعليقات';
                            break;

                        case 'VERBAL5':
                            $harassmentTypeDetails = 'الملاحقة أو التتبع';
                            break;

                        case 'VERBAL6':
                            $harassmentTypeDetails = 'الدعوة الجنسة';
                            break;

                        case 'PHYSICAL1':
                            $harassmentTypeDetails = 'اللمس';
                            break;

                        case 'PHYSICAL2':
                            $harassmentTypeDetails = 'التعري';
                            break;

                        case 'PHYSICAL3':
                            $harassmentTypeDetails = 'التهديد والترهيب';
                            break;

                        case 'PHYSICAL4':
                            $harassmentTypeDetails = 'الاعتداء الجنسي';
                            break;

                        case 'PHYSICAL5':
                            $harassmentTypeDetails = 'الاغتصاب';
                            break;

                        case 'PHYSICAL6':
                            $harassmentTypeDetails = 'التحرش الجماعي';
                            break;
                        
                        default:
                            $harassmentTypeDetails = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . 'تفاصيل اكتر: ' . $harassmentTypeDetails;
                    break;

                case 'assistence_offered':
                    switch ($value) {
                        case '1':
                            $assistenceOffered = 'نعم';
                            break;

                        case '0':
                            $assistenceOffered = 'لا';
                            break;
                        
                        default:
                            $assistenceOffered = '';
                            break;
                    }
                    $preparedReport .= PHP_EOL . 'هل قام المارة بالتدخل للمساعدة؟ ' . $assistenceOffered;
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
}
