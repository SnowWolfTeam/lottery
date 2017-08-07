<?php
namespace LuckyDraw\Code;

use LuckyDraw\Exception\LotteryException;
use LuckyDraw\LotteryInterface\CommonInterface;
use LuckyDraw\Status\Status;

class FairLottery extends LotteryBase implements CommonInterface
{
    public function run($method, LotteryConfig $config)
    {
        $prizeId = $this->lottery($config->probability, $config->prizesCount);

        if (is_null($prizeId)) {
            throw new LotteryException('未中奖', Status::NOT_GET_PRIZE);
        }

        if ($method['datePrizesLimit']) {
            $limit = $config->prizeDateLimit[date('Y-m-d', $this->nowTimeStamp)][$prizeId - 1];
            $this->datePrizesLimit(
                $prizeId,
                empty($limit) ? 0 : $limit,
                $config->prizeDateLimitEvent[0],
                $config->prizeDateLimitEvent[1]
            );
        }

        if ($method['prizesRemaining']) {
            $this->prizesRemaining(
                $prizeId,
                $config->prizesCount[$prizeId - 1],
                $config->prizesCountEvent[0],
                $config->prizesCountEvent[1]
            );
        }

        if ($method['userCanPrize']) {
            $this->userCanPrize(
                $config->userCanPrize[$prizeId - 1],
                $prizeId,
                $config->activityBeginDate,
                $config->repeatData[$prizeId - 1],
                $config->userCanPrizeEvent[0],
                $config->userCanPrizeEvent[1]
            );
        }

        return $prizeId;
    }

    public function lottery($probability, $prizesCount)
    {
        if (!$this->handlerLottery($probability)) {
            return false;
        }

        $preSection = $this->makePreSection($prizesCount, $preSum);
        if (empty($preSection) || (!is_int($preSum) || $preSum <= 1)) {
            throw new LotteryException('无法生成抽奖区间数据', Status::PARAMS_ERROR);
        }

        $index = 1;
        $prizeId = false;
        $randNum = rand(1, $preSum);

        foreach ($preSection as $subPre) {
            if ($randNum >= $subPre[0] && $randNum <= $subPre[1]) {
                $prizeId = $index;
                break;
            }
            $index++;
        }

        return $prizeId;
    }
}