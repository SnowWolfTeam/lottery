<?php
namespace LuckyDraw\Example;
class ExampleConfig
{
    public static $activityDate = [
        '2017-06-06 10:12:00|2017-06-07 11:22:31',
        '2017-06-07 13:20:10|2017-06-09 20:10:00',
        '2017-06-10 12:56:55|2017-06-17 06:14:12',
        '2017-06-20 02:10:33|2017-06-22 12:22:33'
    ];
    public static $timeRegion = ['08:05:12|10:02:33', '11:22:13|13:22:45', '00:01:11|20:45:30'];
    public static $probability = 100;
    public static $prizesCount = [10, 10, 10, 10];
    public static $everyGetPrizes = 10;

    public static $prizesCountEvent = NULL;
    public static $repeatData = [1, 1, 1, 1];
    public static $userCanPrize = [1, 1, 1, 3];
    public static $userCanPrizeEvent = NULL;
    public static $userPrizesLimitEvent = NULL;

    public static $prizeDateLimit = [
        '2017-06-14' => [1, 2, 3, 4],
        '2017-06-15' => [1, 2, 3, 4]
    ];

    public static $prizeDateLimitEvent = NULL;

    public static $activityBeginDate = '2017-06-15 00:00:01';

}