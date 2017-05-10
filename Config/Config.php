<?php
return [
    'activity_date' => ['2017-02-01 10:20:30', '2017-02-07 15:45:55'],
    'times_region' => [['09:10:22', '10:22:31'], ['11:01:22', '13:45:33']],
    'pre_section' => [1, 10, 20, 30, 39],
    'prize_count' => [1, 100, 200, 1000, 10000],
    'repeat_data' => [1800, 1800, 1800, 1800, 1800],
    'user_can_prize' => [1, 1, 1, 1, 1],
    'every_prize_count' => 1,
    'prize_date_limit' => [
        'type' => 1,
        'data' => [
            '2017-1-17' => [10, 20, 30, 40, 50],
            '2017-1-18' => [20, 40, 60, 80, 100]
        ]
    ],
    'every_one_prize_event' => function () {

    },
    'prize_count_event' => function ($level) {

    },
    'date_get_prizes_limit_event' => function ($level) {

    },
    'user_can_prize_event' => function ($level, $cycle) {

    }
];