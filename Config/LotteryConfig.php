<?php
namespace LuckyDraw\Config;
class LotteryConfig
{
    public static $ActivityDate = ['2017-02-01 10:20:30', '2017-09-07 15:45:55'];
    public static $TimesRegion = [['00:00:01', '12:22:31'], ['13:01:22', '14:45:33']];
    public static $PreSection = [1, 10, 20, 30, 39];
    public static $PrizeCount = [1, 100, 200, 1000, 10000];
    public static $RepeatData = [1800, 1800, 1800, 1800, 1800];
    public static $UserCanPrize = [1, 1, 1, 1, 1];
    public static $EveryPrizeCount = 1;
    public static $DefiniteGetPrize = 'desc';
    public static $PrizeNumber = 10;
    public static $PrizeDateLimit = [
        'type' => 1,
        'data' => [
            '2017-05-11' => [10, 20, 30, 40, 50],
            '2017-01-18' => [20, 40, 60, 80, 100]
        ]
    ];

    public static function eventCreate()
    {
        return [
            'definite_prize' => function ($level) {
                return true;
            },
            'total_limit' => function ($abc) {
                return 2;
            },
            'count_prize' => function ($level) {
                return 1;
            },
            'date_limit' => function ($level) {
                return 10;
            },
            'user_limit' => function ($level, $cycle) {
                return 3;
            }
        ];
    }
}