<?php
namespace LuckyDraw;

use LuckyDraw\Code\LotteryConfig;
use LuckyDraw\Exception\LotteryException;
use LuckyDraw\LotteryInterface\CommonInterface;
use LuckyDraw\LotteryInterface\InputInterface;
use LuckyDraw\Status\Status;

class LuckyDraw implements InputInterface
{
    private $lotteryInstance = NULL;

    private $config = NULL;

    private $invokeMethods = [];

    public $prizeId = NULL;

    public function __construct(CommonInterface $instance, $prizesCount, $probability, $countEvent = [])
    {
        $this->lotteryInstance = $instance;
        $this->config = new LotteryConfig();
        $this->config->prizesCount = $prizesCount;
        $this->config->probability = $probability;
        if ($countEvent[0] instanceof \Closure) {
            $this->config->prizesCountEvent = $countEvent;
        }
    }

    public function activityDate($dateRegion)
    {
        if (!is_array($dateRegion) || empty($dateRegion)) {
            throw new LotteryException('activityDate()参数错误', Status::PARAMS_ERROR);
        }
        $this->lotteryInstance->activityDate($dateRegion);
        return $this;
    }

    public function timeRegionLimit($timesRegion)
    {
        if (!is_array($timesRegion)) {
            throw new LotteryException('datePrizesLimit()参数错误', Status::PARAMS_ERROR);
        }

        $this->lotteryInstance->timeRegionLimit($timesRegion);
        return $this;
    }

    public function prizePersonalLimit($limit, $event, $params = [])
    {
        if (!is_int($limit) || $limit < 0 || !($event instanceof \Closure)) {
            throw new LotteryException('datePrizesLimit()参数错误', Status::PARAMS_ERROR);
        }
        $this->lotteryInstance->prizePersonalLimit($limit, $event, $params);
        return $this;
    }


    public function datePrizesLimit($dateLimit, $event, $params = [])
    {
        if (!is_array($dateLimit) || !($event instanceof \Closure)) {
            throw new LotteryException('datePrizesLimit()参数错误', Status::PARAMS_ERROR);
        }
        $this->config->prizeDateLimit = $dateLimit;
        $this->config->prizeDateLimitEvent = [$event, $params];
        $this->invokeMethods['datePrizesLimit'] = true;
        return $this;
    }

    public function userCanPrize($canPrizesArray, $beginDate, $repeatTimesArray, $event, $params = [])
    {
        if (
            !is_array($canPrizesArray)
            || empty($canPrizesArray)
            || empty($beginDate)
            || !is_array($repeatTimesArray)
            || empty($repeatTimesArray)
            || !($event instanceof \Closure)
        ) {
            throw new LotteryException('datePrizesLimit()参数错误', Status::PARAMS_ERROR);
        }

        $this->config->userCanPrize = $canPrizesArray;
        $this->config->activityBeginDate = $beginDate;
        $this->config->repeatData = $repeatTimesArray;
        $this->config->userCanPrizeEvent = [$event, $params];
        $this->invokeMethods['userCanPrize'] = true;
        return $this;
    }

    public function prizesRemaining($event, $params = [])
    {
        if (!($event instanceof \Closure)) {
            throw new LotteryException('datePrizesLimit()参数错误', Status::PARAMS_ERROR);
        }

        $this->config->prizesCountEvent = [$event, $params];
        $this->invokeMethods['prizesRemaining'] = true;
        return $this;
    }

    public function getCycle($beginDate, $repeat)
    {
        // TODO: Implement getCycle() method.
    }

    public function run()
    {
        $this->prizeId = $this->lotteryInstance->run($this->invokeMethods, $this->config);
        return empty($this->prizeId) ? false : $this->prizeId;
    }
}
