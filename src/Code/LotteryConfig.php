<?php
namespace LuckyDraw\Code;
class LotteryConfig
{
    /**
     * @var int �н���
     */
    public $probability = 0;

    /**
     * @var null ��Ʒ����
     */
    public $prizesCount = NULL;

    /**
     * @var null ��Ʒ�����¼�
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