<?php
namespace HarassMapFbMessengerBot\Service;

use HarassMapFbMessengerBot\Report;
use Interop\Container\ContainerInterface;
use DateTime;
use Exception;

class ReportService
{
    const TABLE_REPORTS = 'reports';

    protected $container;
       
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function isUserOnReportDetailsStep(int $userId): bool
    {
        $result = $this->container->dbConnection->fetchAssoc(
            'SELECT count(*) as `reports_count` FROM `' . self::TABLE_REPORTS . '` WHERE `user_id` = ? AND `step` = ?',
            [$userId, Report::STEP_DETAILS]
        );

        return (bool) $result['reports_count'];
    }

    public function isUserOnReportLocationStep(int $userId): bool
    {
        $result = $this->container->dbConnection->fetchAssoc(
            'SELECT count(*) as `reports_count` FROM `' . self::TABLE_REPORTS . '` WHERE `user_id` = ? AND `step` = ?',
            [$userId, Report::STEP_LOCATION]
        );

        return (bool) $result['reports_count'];
    }

    public function setDatetimeForUserReport(string $userId, string $reportId, string $datetime): bool
    {
        try {
            $datetime = new DateTime($datetime);
            return (bool) $this->container->dbConnection->update(
                self::TABLE_REPORTS,
                [
                    'datetime' => $datetime->format('Y-m-d H:i:s'),
                ],
                [
                    'id' => $reportId,
                    'user_id' => $userId
                ]
            );
        } catch (Exception $e) {
            $this->logger->alert($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
            return false;
        }
    }

    public function getInProgressReportByUser(int $id): Report
    {
        $report = $this->container->dbConnection->fetchAssoc(
            'SELECT * FROM `reports` WHERE `user_id` = ? AND `step` != "' . Report::STEP_DONE . '" order by updated_at DESC limit 1',
            [$id]
        );

        if (!is_array($report)) {
            throw new Exception('Can not find report for given user!');
        }

        return new Report(
            $report['user_id'],
            $report['step'],
            $report['relation'],
            $report['details'],
            new DateTime($report['datetime']),
            $report['harassment_type'],
            $report['harassment_type_details'],
            (bool) $report['assistence_offered'],
            $report['latitude'],
            $report['longitude'],
            $report['id'],
            new DateTime($report['created_at']),
            new DateTime($report['updated_at'])
        );
    }

    public function advanceReportStep(Report $report)
    {
        $this->container->dbConnection->update(
            self::TABLE_REPORTS,
            [
                'step' => Report::ORDERED_STEPS[array_search($report->getStep(), Report::ORDERED_STEPS) + 1]
            ],
            [
                'id' => $report->getId()
            ]
        );
    }

    public function saveAnswerToReport(string $field, string $answer, Report $report)
    {
        $this->container->dbConnection->update(
            self::TABLE_REPORTS,
            [
                $field => $answer
            ],
            [
                'id' => $report->getId()
            ]
        );
    }

    public function startReportForUser(int $userId)
    {
        $this->container->dbConnection->insert(self::TABLE_REPORTS, [
            'user_id' => $userId,
            'step' => Report::STEP_INIT,
        ]);
    }
}
