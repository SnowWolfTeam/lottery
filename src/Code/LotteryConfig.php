<?php
namespace LuckyDraw\Code;
class LotteryConfig
{
    /**
     * @var int 中奖率
     */
    public $probability = 0;

    /**
     * @var null 奖品数量
     */
    public $prizesCount = NULL;

    /**
     * @var null 奖品计数事件
     */
    public $prizesCountEvent = NULL;

    /**
     * @var null
     */
    public $repeatData = NULL;
    public $userCanPrize = NULL;
    public $userCanPrizeEvent = NULL;
    public $userPrizesLimitEvent = NULL;
    public $prizeDateLimit = NULL;
    public $prizeDateLimitEvent = NULL;

    public $activityBeginDate = NULL;
}