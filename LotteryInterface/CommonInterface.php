<?php
namespace LuckyDraw\LotteryInterface;
use LuckyDraw\Code\LotteryConfig;

interface CommonInterface
{
    public function run($method,LotteryConfig $config);

    public function lottery($probability,$prizesCount);
}