<?php
namespace LuckyDraw\LotteryInterface;
interface InputInterface
{
    public function activityDate($dateRegion);

    public function timeRegionLimit($timesRegion);

    public function datePrizesLimit($dateLimit, $event, $params = []);

    public function prizesRemaining($event, $params = []);

    public function prizePersonalLimit($limit, $event, $params = []);

    public function userCanPrize($canPrizesArray, $beginDate, $repeatTimesArray, $event, $params = []);

    public function getCycle($beginDate, $repeat);
}