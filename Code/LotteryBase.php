<?php
namespace LuckyDraw\Code;

use LuckyDraw\Exception\LotteryException;
use LuckyDraw\Status\Status;

class LotteryBase
{
    //timestamp create by function time()
    protected $nowTimeStamp = 0;

    /**
     * Todo 依赖外部传入,保证单元测试
     * LotteryBase constructor.
     */
    public function __construct()
    {
        $this->nowTimeStamp = time();
    }

    /**
     * Decide the activity whether closed or not.
     * (检查当前时间是否在活动时间内)
     */
    public function activityDate($dateRegion)
    {
        $exist = false;

        foreach ($dateRegion as $date) {
            list($begin, $end) = explode('|', $date);

            if (
                $this->nowTimeStamp >= strtotime($begin) &&
                $this->nowTimeStamp <= strtotime($end)
            ) {
                $exist = true;
                break;
            }
        }

        if (!$exist) {
            throw new LotteryException("活动结束", Status::ACTIVITY_END);
        }
    }

    /**
     * Whether user can get a prize in a set of time period or not.
     * (检查当前时间是否在可以可抽奖时间内)
     */
    public function timeRegionLimit($timesRegion)
    {
        $exist = false;
        $nowTime = time();
        $ymd = date('Y-m-d ', $this->nowTimeStamp);

        foreach ($timesRegion as $region) {
            list($begin, $end) = explode('|', $region);

            if (
                $nowTime > strtotime($ymd . $begin) &&
                $nowTime < strtotime($ymd . $end)
            ) {
                $exist = true;
                break;
            }
        }

        if (!$exist) {
            throw new LotteryException("不在可抽奖的时间段内", Status::NOT_LOTTERY_TIME_REGION);
        }
    }

    /**
     * Whether the user's toatl numbers of prize reach the maximum limit.
     * (用户个人中奖数限制)
     */
    public function prizePersonalLimit($limit, $event, $params = [])
    {
        $count = call_user_func_array($event, is_array($params) ? $params : [$params]);

        if ($count >= $limit || !is_int($count) || $count < 0) {
            throw new LotteryException("用户中奖数量已达到限制值", Status::USER_TOTAL_LIMIT_REACH);
        }
    }

    /**
     * Get the prize's number which has been drawed.
     * (根据奖品id获取剩余奖品的数量)
     */
    public function prizesRemaining($prizeId, $prizeNum, $event, $params = [])
    {
        if (!empty($params) && !is_array($params)) {
            $params = [$params];
        }

        array_unshift($params, $prizeId);

        $prizeCount = call_user_func_array($event, $params);

        if ($prizeCount >= $prizeNum || !is_int($prizeCount) || $prizeCount < 0) {
            throw new LotteryException("此等奖已经全部送出", Status::THIS_PRIZE_OUT);
        }
    }

    /**
     * Whether the prize's limit of today has been reached.
     * (每天奖品送出数量)
     */
    public function datePrizesLimit($prizeId, $prizeLimit, $event, $params = [])
    {
        if (!is_int($prizeLimit) || $prizeLimit <= 0) {
            throw new LotteryException("当前奖品送出已达上限", Status::DATE_PRIZES_LIMIT);
        }

        if (!empty($params) && !is_array($params)) {
            $params = [$params];
        }

        array_unshift($params, $prizeId);

        $count = call_user_func_array($event, $params);

        if (!is_int($count) || $count < 0 || $count >= $prizeLimit) {
            throw new LotteryException("当前奖品送出已达上限", Status::DATE_PRIZES_LIMIT);
        }
    }

    /**
     * Whether user can get the prizes again.
     */
    public function userCanPrize($limit, $prizeId, $beginDate, $repeatTimes, $event, $params = [])
    {
        $cycle = $this->getCycle($beginDate, $repeatTimes);

        if (!is_array($params)) {
            $params = [$params];
        }

        array_unshift($params, $cycle);
        array_unshift($params, $prizeId);

        $count = call_user_func_array($event, $params);

        if (!is_int($count) || $count < 0 || $count >= $limit) {
            throw new LotteryException("当前用户的抽奖限制还没刷新", Status::USER_CAN_NOT_PRIZE_NOW);
        }
    }

    /**
     * Make the pre section.
     */
    public function makePreSection($pre, &$preSum)
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
    public function getCycle($beginDate, $repeat)
    {
        $beginStamp = strtotime($beginDate);
        $second = $this->nowTimeStamp - $beginStamp;
        $cycle = (int)($second / $repeat) + 1;
        return $cycle;
    }

    /**
     * Get the result whether is get a prize.
     */
    public function handlerLottery($probability)
    {
        if ($probability === 0) {
            return false;
        }

        if ($probability === 100 || $probability > 100) {
            return true;
        }

        list($head, $tail) = explode('.', (string)$probability);

        if (!empty($tail) && (int)$tail !== 0) {
            $totalNumber = 100 * (10 * pow(10, strlen($tail)));
            $prizeNumber = (int)($head . $tail);
        } else {
            $totalNumber = 100;
            $prizeNumber = (int)$head;
        }

        $number = rand(1, $totalNumber);
        return ($number <= $prizeNumber);
    }
}