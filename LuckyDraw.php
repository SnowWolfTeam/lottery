<?php
namespace LuckyDraw;

use LuckyDraw\Events\Events;

class LuckyDraw
{
    const PARAMS_NULL = 0x1000;
    const ACTIVITY_OUT = 0x1001;
    const NOT_LOTTERY_TIME_REGION = 0x1002;
    const PRIZE_TOTAL_LIMIT_REACH = 0x1003;
    const TOTAL_PRE_ERROR = 0x1004;
    const THIS_PRIZE_OUT = 0x1005;
    const DATE_PRIZES_LIMIT = 0x1006;
    const USER_CAN_NOT_PRIZE_NOW = 0x1007;
    const EVENT_NOT_EXIST = 0x1008;
    const EVENT_RETURN_ERROR = 0x1009;

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
    private $eventInstance = NULL;

    /**
     * Lottery config data.
     */
    private $config = [];

    /**
     * Lottery function  execute result.
     */
    public $status = -1;

    /**
     * LuckyDraw constructor.
     */
    public function __construct($config = [])
    {
        if (is_array($config) && !empty($config)) {
            $this->config = $config;
        } elseif (is_string($config) && !empty($config) && is_file($config)) {
            $data = include $config;
            if (is_array($data) && !empty($data))
                $this->config = $data;
        } else {
            $configPath = __DIR__ . '/Config/Config.php';
            if (is_file($configPath)) {
                $data = include $configPath;
                if (is_array($data) && !empty($data))
                    $this->config = $data;
            }
        }
        $this->eventInstance = new Events();
        $this->eventRegister();
    }

    /**
     * Register events from the config data.
     */
    private function eventRegister()
    {
        if (isset($this->config['every_one_prize_event'])) {
            $this->eventInstance->setEvents(
                'every_one_prize_event',
                $this->config['every_one_prize_event']
            );
        }
        if (isset($this->config['prize_count_event'])) {
            $this->eventInstance->setEvents(
                'prize_count_event',
                $this->config['prize_count_event']
            );
        }
        if (isset($this->config['date_get_prizes_limit_event'])) {
            $this->eventInstance->setEvents(
                'date_get_prizes_limit_event',
                $this->config['date_get_prizes_limit_event']
            );
        }
        if (isset($this->config['user_can_prize_event'])) {
            $this->eventInstance->setEvents(
                'user_can_prize_event',
                $this->config['user_can_prize_event']
            );
        }
    }

    /**
     * Decide the activity whether closed or not.
     */
    public function activityDate($dateRegion = [])
    {
        if ($this->status == -1 && $this->level != $this->config['shared_prize']) {
            $date = $this->config['activity_date'];
            $date = empty($dateRegion) ? (empty($date) ? [] : $date) : $dateRegion;
            if (empty($date))
                $this->status = self::PARAMS_NULL;
            else {
                $nowTime = $_SERVER['REQUEST_TIME'];
                if ($nowTime < strtotime($date[0]) || $nowTime > strtotime($date[1]))
                    $this->status = self::ACTIVITY_OUT;
            }
        }
        return $this;
    }

    /**
     * Whether user can get a prize in a set of time period or not.
     */
    public function timesPermitRegion($timesRegion = [])
    {
        if ($this->status == -1 && $this->level != $this->config['shared_prize']) {
            $region = $this->config['times_region'];
            $region = empty($timesRegion) ? (empty($region) ? [] : $region) : $timesRegion;
            if (!empty($region)) {
                $result = false;
                $nowTimeStamp = $_SERVER['REQUEST_TIME'];
                $ymd = date('Y-m-d ', $_SERVER['REQUEST_TIME']);
                foreach ($region as $values) {
                    if ($nowTimeStamp > strtotime($ymd . $values[0])
                        && $nowTimeStamp < strtotime($ymd . $values[1])
                    ) {
                        $result = true;
                        break;
                    }
                }
                if (!$result)
                    $this->status = self::NOT_LOTTERY_TIME_REGION;
            } else
                $this->status = self::PARAMS_NULL;
        }
        return $this;
    }

    /**
     * Whether the user's toatl numbers of prize reach the maximum limit.
     */
    public function everyOnePrizeCount($limit = -1, $params = [])
    {
        if ($this->status == -1 && $this->level != $this->config['shared_prize']) {
            if ($this->eventInstance->exist('every_one_prize_event')) {
                if ($limit == -1) $limit = $this->config['every_prize_count'];
                if (is_int($limit) && $limit >= 0) {
                    $count = $this->eventInstance->run('every_one_prize_event', $params);
                    if (is_int($count) && $count >= 0) {
                        if ($count >= $limit)
                            $this->status = self::PRIZE_TOTAL_LIMIT_REACH;
                    } else
                        $this->status = self::EVENT_RETURN_ERROR;
                } else
                    $this->status = self::PARAMS_NULL;

            } else
                $this->status = self::EVENT_NOT_EXIST;
        }
        return $this;
    }

    /**
     * To draw.
     */
    private function lottery()
    {
        if ($this->status == -1 && $this->level != $this->config['shared_prize']) {
            $pre = $this->config['pre_section'];
            $pre = empty($preSection) ? (empty($pre) ? [] : $pre) : $preSection;
            if (empty($pre))
                $this->status = self::PARAMS_NULL;
            else {
                $pre = $this->makePreSection($pre, $preSum);
                if (is_int($preSum) && $preSum >= 1) {
                    $randNum = rand(1, $preSum);
                    $i = 1;
                    $prizeLevel = 0;
                    foreach ($pre as $subPre) {
                        if (($randNum == $subPre[0])
                            || ($randNum == $subPre[1])
                            || ($randNum > $subPre[0] && $randNum < $subPre[1])
                        ) {
                            $prizeLevel = $i;
                            break;
                        }
                        $i++;
                    }
                    $this->level = $prizeLevel;
                } else
                    $this->status = self::TOTAL_PRE_ERROR;
            }
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
     * Get the prize's number which has been drawed.
     */
    public function prizeCount($prizeCount = [])
    {
        if ($this->status == -1 && $this->level != $this->config['shared_prize']) {
            $prizes = $this->config['prize_count'];
            $prizes = empty($prizeCount) ? (empty($prizes) ? [] : $prizes) : $prizeCount;
            if (empty($prizes)) {
                $this->status = self::PARAMS_NULL;
            } else {
                if($this->level == -1){
                    $this->lottery();
                }
                if ($this->eventInstance->exist('prize_count_event')) {
                    $levelCount = $this->eventInstance->run('prize_count_event', [$this->level]);
                    if (is_int($levelCount) && $levelCount >= 0) {
                        $levelIndex = $this->level - 1;
                        if ($prizes[$levelIndex] <= $levelCount)
                            $this->status = self::THIS_PRIZE_OUT;
                    } else
                        $this->status = self::EVENT_RETURN_ERROR;
                } else
                    $this->status = self::EVENT_NOT_EXIST;
            }
        }
        return $this;
    }

    /**
     * Whether the prize's limit of today has been reached.
     */
    public function dateGetPrizesLimit($prizeLimit = [])
    {
        if ($this->status == -1 && $this->level != $this->config['shared_prize']) {
            $prizeDateLimit = $this->config['prize_date_limit'];
            $prizeDateLimit = empty($prizeLimit) ? (empty($prizeDateLimit) ? [] : $prizeDateLimit) : $prizeLimit;
            if (empty($prizeDateLimit))
                $this->status = self::PARAMS_NULL;
            else {
                if($this->level == -1){
                    $this->lottery();
                }
                $limitCount = -1;
                $requireTime = $_SERVER['REQUEST_TIME'];
                $dateString = date('Y-m-d', $requireTime);
                $levelIndex = $this->level - 1;
                if ($prizeDateLimit['type'] == 1) {
                    $limitCount = $prizeDateLimit['data'][$dateString][$levelIndex];
                } else {
                    $data = $prizeDateLimit['data'];
                    foreach ($data as $key => $values) {
                        $region = explode('|', $key);
                        if (sizeof($region) < 2)
                            continue;
                        $begin = strtotime($region[0]);
                        $end = strtotime($region[1]);
                        if ($begin === false || $end === false)
                            continue;
                        $thisDay = date('Y-m-d', $begin);
                        if ($thisDay != $dateString)
                            continue;
                        elseif ($requireTime > $begin && $requireTime < $end) {
                            $limitCount = $data[$levelIndex];
                            break;
                        } else
                            continue;
                    }
                }
                if ($limitCount === -1)
                    $this->status = self::DATE_PRIZES_LIMIT;
                if ($this->eventInstance->exist('date_get_prizes_limit_event')) {
                    $count = $this->eventInstance->run('date_get_prizes_limit_event', [$this->level]);
                    if (is_int($count) && $count >= 0) {
                        if ($count >= $limitCount)
                            $this->status = self::DATE_PRIZES_LIMIT;
                    } else
                        $this->status = self::EVENT_RETURN_ERROR;
                } else
                    $this->status = self::EVENT_NOT_EXIST;
            }
        }
        return $this;
    }

    /**
     * Whether user can get the prizes again.
     */
    public function userCanPrize($userCanPrize = [], $beginDate = '', $repeatDate = '')
    {
        if ($this->status == -1 && $this->level != $this->config['shared_prize']) {
            $userPrize = $this->config['user_can_prize'];
            $userPrize = empty($userCanPrize) ? (empty($userPrize) ? [] : $userPrize) : $userCanPrize;
            $begin = $this->config['activity_date'][0];
            $begin = empty($beginDate) ? (empty($begin) ? '' : $begin) : $beginDate;
            $repeat = $this->config['repeat_data'][$this->level - 1];
            $repeat = empty($repeatDate) ? (empty($repeat) ? '' : $repeat) : $repeatDate;
            if (empty($userPrize) || empty($begin) || empty($repeat))
                $this->status = self::PARAMS_NULL;
            else {
                if ($this->eventInstance->exist('user_can_prize_event')) {
                    if($this->level == -1){
                        $this->lottery();
                    }
                    $count = 0;
                    $limit = $userPrize[$this->level - 1];
                    $cycle = $this->getCycle($begin, $repeat);
                    $count = $this->eventInstance->run('user_can_prize_event', [$this->level, $cycle]);
                    if (is_int($count) && $count >= 0) {
                        if ($count >= $limit)
                            $this->status = self::USER_CAN_NOT_PRIZE_NOW;
                    } else
                        $this->status = self::EVENT_RETURN_ERROR;
                } else
                    $this->status = self::EVENT_NOT_EXIST;
            }
        }
    }

    /**
     * Make cycle with $beginDate and $repeatData.
     */
    private function getCycle($beginDate, $repeatData)
    {
        $beginStamp = strtotime($beginDate);
        $second = time() - $beginStamp;
        $cycle = (int)((int)($second / 3600) / $repeatData) + 1;
        return $cycle;
    }
}