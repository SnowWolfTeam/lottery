<?php
namespace LuckyDraw\Code;

use LuckyDraw\Exception\LotteryException;
use LuckyDraw\LotteryInterface\CommonInterface;
use LuckyDraw\Status\Status;

class OutLottery extends LotteryBase implements CommonInterface
{

    public function run($method, LotteryConfig $config)
    {
        if ($this->handlerLottery($config->probability)) {
            $prizeArray = [];
            $prizeSum = sizeof($config->prizesCount);
            for ($i = 1; $i <= $prizeSum; $i++) {
                $prizeArray[$i] = $i;
            }
            if ($method['datePrizesLimit']) {
                $prizeLimitArray = $config->prizeDateLimit[date('Y-m-d', $this->nowTimeStamp)];
                foreach ($prizeArray as $key => $prizeId) {
                    try {
                        $this->datePrizesLimit(
                            $prizeId,
                            $prizeLimitArray[$prizeId - 1],
                            $config->prizeDateLimitEvent[0],
                            $config->prizeDateLimitEvent[1]
                        );
                    } catch (LotteryException $exception) {
                        unset($prizeArray[$key]);
                    }
                }
            }
            if ($method['userCanPrize']) {
                foreach ($prizeArray as $key => $prizeId) {
                    try {
                        $this->userCanPrize(
                            $config->userCanPrize[$prizeId - 1],
                            $prizeId,
                            $config->activityBeginDate,
                            $config->repeatData[$prizeId - 1],
                            $config->userCanPrizeEvent[0],
                            $config->userCanPrizeEvent[1]
                        );
                    } catch (LotteryException $exception) {
                        unset($prizeArray[$key]);
                    }
                }
            }
            if (!$method['datePrizesLimit']) {
                foreach ($prizeArray as $key => $prizeId) {
                    try {
                        $this->prizesRemaining(
                            $prizeId,
                            $config->prizesCount[$prizeId - 1],
                            $config->prizesCountEvent[0],
                            $config->prizesCountEvent[1]
                        );
                    } catch (LotteryException $exception) {
                        unset($prizeArray[$key]);
                    }
                }
            }
            if (sizeof($prizeArray) == 0) {
                throw new LotteryException('未中奖', Status::NOT_GET_PRIZE);
            } elseif (sizeof($prizeArray) == 1) {
                return end($prizeArray);
            } else {
                $prizeCount = [];
                foreach ($prizeArray as $key => $count) {
                    $prizeCount[$key] = $config->prizesCount[$key - 1];
                }
                return $this->lottery(0, $prizeCount);
            }
        } else {
            throw new LotteryException('未中奖', Status::NOT_GET_PRIZE);
        }
    }

    /**
     * To draw.
     */
    public function lottery($probability, $prizeCount)
    {
        $preSection = $this->makePreSection($prizeCount, $preSum);
        if (empty($preSection) || (!is_int($preSum) || $preSum <= 1))
            throw new LotteryException('无法生成抽奖区间数据', Status::PARAMS_ERROR);
        $randNum = rand(1, $preSum);
        $index = 1;
        $prizeId = -1;
        foreach ($preSection as $subPre) {
            if ($randNum >= $subPre[0] && $randNum <= $subPre[1]) {
                $prizeId = $index;
                break;
            }
            $index++;
        }
        $index = 1;
        foreach ($prizeCount as $key => $value) {
            if ($index == $prizeId) {
                $prizeId = $key;
                break;
            }
            $index++;
        }
        return $prizeId;
    }
}