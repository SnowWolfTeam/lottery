<?php
namespace LuckyDraw;

use LuckyDraw\Config\LotteryConfig;
use LuckyDraw\Events\Events;
use LuckyDraw\Exception\LotteryException;
use LuckyDraw\Status\Status;

class LuckyDraw
{
    /**
     * Lottery prize's level.
     */
    public $level = -1;//奖品抽奖

    /**
     * Now lottery time's cycle.
     */
    public $cycle = 0;//周期

    /**
     * Event class instance.
     */
    private $event = NULL;

    /**
     * LuckyDraw constructor.
     */
    public function __construct($event = [])
    {
        $this->event = new Events();
        $this->eventInitialization($event);
    }

    /**
     * Register events from the config data.
     */
    private function eventInitialization($event = [])
    {
        if (empty($event)) {
            $event = LotteryConfig::eventCreate();
        }
        if (!empty($event)) {
            if (isset($event['definite_prize'])) {
                $this->event->setEvents('definite_prize', $event['definite_prize']);
            }
            if (isset($event['total_limit'])) {
                $this->event->setEvents('total_limit', $event['total_limit']);
            }
            if (isset($event['count_prize'])) {
                $this->event->setEvents('count_prize', $event['prize_count']);
            }
            if (isset($event['date_limit'])) {
                $this->event->setEvents('date_limit', $event['date_limit']);
            }
            if (isset($event['user_limit'])) {
                $this->event->setEvents('user_limit', $event['user_limit']);
            }
            foreach ($event as $key => $value) {
                $this->event->setEvents($key, $value);
            }
        }
    }

    /**
     * Register user's event.
     */
    public function eventRegister($eventName, $event)
    {
        $this->event->setEvents($eventName, $event);
    }

    /**
     * Decide the activity whether closed or not.
     */
    public function activityDate($dateRegion = [])
    {
        !empty($dateRegion) or $dateRegion = LotteryConfig::$ActivityDate;
        $this->paramsThrow(empty($dateRegion), 'activityDate()');
        $nowTime = $_SERVER['REQUEST_TIME'];
        if (!is_array($dateRegion[0])) {
            if ($nowTime < strtotime($dateRegion[0]) ||
                $nowTime > strtotime($dateRegion[1])
            ) {
                throw new LotteryException("活动结束", Status::ACTIVITY_END);
            }
        } else {
            $exist = false;
            foreach ($dateRegion as $dates) {
                if ($nowTime < strtotime($dates[0]) ||
                    $nowTime > strtotime($dates[1])
                ) {
                    $exist = true;
                    break;
                }
            }
            if (!$exist) {
                throw new LotteryException("活动结束", Status::ACTIVITY_END);
            }
        }
        return $this;
    }

    /**
     * Whether user can get a prize in a set of time period or not.
     */
    public function timeRegionLimit($timesRegion = [])
    {
        !empty($timesRegion) or $timesRegion = LotteryConfig::$TimesRegion;
        $this->paramsThrow(empty($timesRegion), 'timeRegionLimit()');
        $result = false;
        $nowTimeStamp = $_SERVER['REQUEST_TIME'];
        $ymd = date('Y-m-d ', $_SERVER['REQUEST_TIME']);
        foreach ($timesRegion as $values) {
            if ($nowTimeStamp > strtotime($ymd . $values[0])
                && $nowTimeStamp < strtotime($ymd . $values[1])
            ) {
                $result = true;
                break;
            }
        }
        if (!$result) {
            throw new LotteryException("不在可抽奖的时间段内", Status::NOT_LOTTERY_TIME_REGION);
        }
        return $this;
    }

    /**
     * Whether the user's toatl numbers of prize reach the maximum limit.
     */
    public function userPrizeCount($limit = -1, $eventName = '', $params = [])
    {
        !empty($eventName) or $eventName = 'total_limit';
        $this->eventThrow($eventName);
        ($limit != -1) or $limit = LotteryConfig::$EveryPrizeCount;
        $this->paramsThrow((!is_int($limit) || $limit < 0), 'userPrizeCount');
        $count = $this->event->run($eventName, (is_array($params) ? $params : [$params]));
        if ($count >= $limit) {
            throw new LotteryException("用户中奖数量已达到限制值", Status::USER_TOTAL_LIMIT_REACH);
        }
        return $this;
    }

    /**
     * To draw.
     */
    private function lottery($preSection = [])
    {
        !empty($preSection) or $preSection = LotteryConfig::$PreSection;
        $preSection = $this->makePreSection($preSection, $preSum);
        $this->paramsThrow(empty($preSection) || (!is_int($preSum) || $preSum <= 1), 'lottery()');
        $randNum = rand(1, $preSum);
        $index = 1;
        foreach ($preSection as $subPre) {
            if ($randNum >= $subPre[0] && $randNum <= $subPre[1]) {
                $this->level = $index;
                break;
            }
            $index++;
        }
        return $this;
    }

    /**
     * Get the prize's number which has been drawed.
     */
    public function prizeCount($prizeCount = [], $eventName = '', $params = [])
    {
        !empty($eventName) or $eventName = 'count_prize';
        $this->eventThrow($eventName);
        !empty($prizeCount) or $prizeCount = LotteryConfig::$PrizeCount;
        $this->paramsThrow(empty($prizeCount), 'prizeCount()');
        ($this->level != -1) or $this->lottery();
        if (!empty($params)) {
            if (!is_array($params)) {
                $params = [$params];
            }
            array_unshift($params, $this->level);
        }
        $levelCount = $this->event->run($eventName, $params);
        if ($prizeCount[$this->level - 1] <= $levelCount) {
            throw new LotteryException("此等奖已经全部送出", Status::THIS_PRIZE_OUT);
        }
        return $this;
    }

    /**
     * Whether the prize's limit of today has been reached.
     */
    public function datePrizesLimit($prizeLimit = [], $eventName = '', $params = [])
    {
        !empty($eventName) or $eventName = 'date_limit';
        $this->eventThrow($eventName);
        !empty($prizeLimit) or $prizeLimit = LotteryConfig::$PrizeDateLimit;
        $this->paramsThrow(empty($prizeLimit), 'datePrizesLimit');
        ($this->level != -1) or $this->lottery();
        $limitCount = -1;
        $requireTime = $_SERVER['REQUEST_TIME'];
        $dateString = date('Y-m-d', $requireTime);
        if ($prizeLimit['type'] == 1) {
            $limitCount = $prizeLimit['data'][$dateString][$this->level - 1];
        } else {
            $data = $prizeLimit['data'];
            foreach ($data as $key => $values) {
                $region = explode('|', $key);
                $begin = strtotime($region[0]);
                $end = strtotime($region[1]);
                if (sizeof($region) < 2 || $begin === false ||
                    $end === false || date('Y-m-d', $begin) != $dateString
                ) {
                    continue;
                }
                if ($requireTime >= $begin && $requireTime <= $end) {
                    $limitCount = $data[$this->level - 1];
                    break;
                }
            }
        }
        if ($limitCount === -1) {
            throw new LotteryException("当前奖品送出已达上限", Status::DATE_PRIZES_LIMIT);
        }
        if (!empty($params) && !is_array($params)) {
            $params = [$params];
        }
        array_unshift($params, $this->level);
        $count = $this->event->run($eventName, $params);
        if ($count >= $limitCount) {
            throw new LotteryException("当前奖品送出已达上限", Status::DATE_PRIZES_LIMIT);
        }
        return $this;
    }

    /**
     * Whether user can get the prizes again.
     */
    public function userCanPrize($userCanPrize = [], $beginDate = '', $repeat = 1, $eventName = '', $params = [])
    {
        !empty($eventName) or $eventName = 'user_limit';
        $this->eventThrow($eventName);
        ($this->level != -1) or $this->lottery();
        !empty($userCanPrize) or $userCanPrize = LotteryConfig::$UserCanPrize;
        !empty($beginDate) or $beginDate = (LotteryConfig::$ActivityDate)[0];
        !empty($repeat) or $repeat = (LotteryConfig::$RepeatData)[$this->level - 1];
        $this->paramsThrow((empty($userCanPrize) || empty($beginDate) || ($repeat <= 0)), 'userCanPrize()');
        $limit = $userCanPrize[$this->level - 1];
        $cycle = $this->getCycle($beginDate, $repeat);
        $count = $this->event->run($eventName, [$this->level, $cycle]);
        if ($count >= $limit)
            throw new LotteryException("当前用户的抽奖限制还没刷新", Status::USER_CAN_NOT_PRIZE_NOW);
        return $this;
    }

    /**
     * Make sure user can get a prize.If all prize out,then user get nothing.
     */
    public function definiteGetPrize($order = 'desc', $size = 0, $eventName = '', $params = [])
    {
        !empty($order) or $order = LotteryConfig::$DefiniteGetPrize;
        !empty($size) or $size = LotteryConfig::$PrizeNumber;
        $this->paramsThrow((($order != 'desc' && $order != 'asc') || $size <= 0), 'definiteGetPrize');
        !empty($eventName) or $eventName = 'definite_prize';
        $this->eventThrow($eventName);
        $prizes = [];
        for ($i = 1; $i <= $size; $i++) {
            $prizes[] = $i;
        }
        if ($order == 'desc') {
            $prizes = array_reverse($prizes);
        }
        $funcResult = false;
        foreach ($prizes as $prize) {
            $temp = $params;
            array_unshift($temp, $prize);
            $result = $this->event->run($eventName, $temp);
            if ($result) {
                $this->level = $i;
                $funcResult = true;
                break;
            }
        }
        if (!$funcResult) {
            throw new LotteryException('奖品已经全部送出', Status::PRIZE_ALL_OUT);
        }
        return $this;
    }

    /**
     * Make the pre section.
     */
    private function makePreSection($pre, &$preSum)
    {
        $preixSum = 0;
        $nextSum = 0;
        $preSection = [];
        foreach ($pre as $values) {
            $nextSum += $values;
            $preSection[] = [$preixSum + 1, $nextSum];
            $preixSum = $nextSum;
        }
        $preSum = $nextSum;
        return $preSection;
    }

    /**
     * Make cycle with $beginDate and $repeatData.
     */
    private function getCycle($beginDate, $repeat)
    {
        $beginStamp = strtotime($beginDate);
        $second = time() - $beginStamp;
        $cycle = (int)((int)($second / 3600) / $repeat) + 1;
        return $cycle;
    }

    /**
     * Check event exist,if not exist then throw a exception.
     */
    public function eventThrow($eventName)
    {
        if (!$this->event->exist('$eventName')) {
            throw new LotteryException("{$eventName}事件缺失", Status::EVENT_NOT_EXIST);
        }
    }

    /**
     * Check method's params whether is lawful.If not then throw a excetpion.
     */
    public function paramsThrow($bool, $methodName)
    {
        if ($bool) {
            throw new LotteryException("{$methodName}事件缺失", Status::PARAMS_ERROR);
        }
    }
}
