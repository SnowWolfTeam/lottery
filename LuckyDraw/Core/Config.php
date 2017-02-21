<?php
return [
    'func' => [
        'CPAD' => true,
        'CTPR' => true,
        'EOP' => true,
        'CTGPL' => true,
        'CPC' => true,
        'CUCP' => true
    ],
    'activityDate' => ['2017-02-01 10:20:30', '2017-02-07 15:45:55'],
    'timesRegion' => [['09:10:22', '10:22:31'], ['11:01:22', '13:45:33']],
    'pdo' => ['dsn' => '', 'user' => 'mysql', 'passWord' => '1234'],
    'preSection' => [1,10, 20, 30, 39],
    'sparePrizeId' => 5,
    'prizeCount' => [1, 100, 200, 1000, 10000],//每个奖品的数量
    'repeatData' => [1800, 1800, 1800, 1800, 1800],//每个奖品的刷新时间
    'userCanPrize' => [1, 1, 1, 1, 1],//每个奖品当前用户可抽的次数
    'prizeDateLimit' => [
        '2017-1-17' => [10, 20, 30],
        '2017-1-18' => [20, 40, 60],
        '2017-1-19' => [30, 60, 90]
    ],
    'spl' => [
        'origin' => '',
        'levelCondition' => '',
        'userCondition' => '',
        'cycleCondition' => ''
    ]
];