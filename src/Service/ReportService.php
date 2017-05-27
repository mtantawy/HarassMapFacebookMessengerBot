<?php
namespace HarassMapFbMessengerBot\Service;

use HarassMapFbMessengerBot\Report;
use Interop\Container\ContainerInterface;
use DateTime;

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
}
