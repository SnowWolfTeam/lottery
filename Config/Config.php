<?php
return [
    'activity_date' => ['2017-02-01 10:20:30', '2017-09-07 15:45:55'],
    'times_region' => [['00:00:01', '12:22:31'], ['13:01:22', '14:45:33']],
    'pre_section' => [1, 10, 20, 30, 39],
    'prize_count' => [1, 100, 200, 1000, 10000],
    'repeat_data' => [1800, 1800, 1800, 1800, 1800],
    'user_can_prize' => [1, 1, 1, 1, 1],
    'every_prize_count' => 1,
    'definite_get_prize' => [
        'order' => 'desc'
    ],
    'shared_pirze' => 5,
    'prize_number' => 10,
    'prize_date_limit' => [
        'type' => 1,
        'data' => [
            '2017-05-11' => [10, 20, 30, 40, 50],
            '2017-01-18' => [20, 40, 60, 80, 100]
        ]
    ],
    'definite_get_prize_event' => function ($level) {
        return true;
    },
    'every_one_prize_event' => function ($abc) {
        return 2;
    },
    'prize_count_event' => function ($level) {
        return 1;
    },
    'date_get_prizes_limit_event' => function ($level) {
        return 10;
    },
    'user_can_prize_event' => function ($level, $cycle) {
        return 3;
    }
];