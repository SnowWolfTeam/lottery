<?php
require "ExampleConfig.php";

use LuckyDraw\Example\ExampleConfig;
use LuckyDraw\LuckyDraw;
use LuckyDraw\Code\FairLottery;
use think\Db;

$luckDraw = new LuckyDraw(
    new FairLottery(),
    ExampleConfig::$prizesCount,
    ExampleConfig::$probability
);

//1 . 活动日期
$luckDraw->activityDate(ExampleConfig::$activityDate);
var_dump($luckDraw->prizeId);

//2 . 活动日期+抽奖时间段
$luckDraw->activityDate(ExampleConfig::$activityDate)->timeRegionLimit(ExampleConfig::$timeRegion);
var_dump($luckDraw->prizeId);

//3 . 活动日期+抽奖时间段+每人中奖数
$luckDraw->activityDate(ExampleConfig::$activityDate)
    ->timeRegionLimit(ExampleConfig::$timeRegion)
    ->prizePersonalLimit(ExampleConfig::$everyGetPrizes,
        function ($name) {
            $count = Db::table('prize')->where(['name' => $name])->count();
            return $count;
        }, ['thr']
    );
var_dump($luckDraw->prizeId);

//4 . 活动日期+抽奖时间段+每人中奖数+日期送出奖品数
$luckDraw->activityDate(ExampleConfig::$activityDate)
    ->timeRegionLimit(ExampleConfig::$timeRegion)
    ->prizePersonalLimit(ExampleConfig::$everyGetPrizes,
        function ($name) {
            $count = Db::table('prize')->where(['name' => $name])->count();
            return $count;
        }, ['thr']
    )->datePrizesLimit(ExampleConfig::$prizeDateLimit,
        function ($prizeId) {
            $count = Db::table('prize')->where(['prize' => $prizeId])->count();
            return $count;
        })
    ->run();
var_dump($luckDraw->prizeId);

//5 . 活动日期+抽奖时间段+每人中奖数+日期送出奖品数+奖品总数检查
$luckDraw->activityDate(ExampleConfig::$activityDate)
    ->timeRegionLimit(ExampleConfig::$timeRegion)
    ->prizePersonalLimit(ExampleConfig::$everyGetPrizes,
        function ($name) {
            $count = Db::table('prize')->where(['name' => $name])->count();
            return $count;
        }, ['thr']
    )->datePrizesLimit(ExampleConfig::$prizeDateLimit,
        function ($prizeId) {
            $count = Db::table('prize')->where(['prize' => $prizeId])->count();
            return $count;
        })
    ->prizesRemaining(function ($prizeId) {
        $count = Db::table('prize')->where(['prize' => $prizeId])->count();
        return $count;
    })
    ->run();
var_dump($luckDraw->prizeId);

//6 . 活动日期+抽奖时间段+每人中奖数+日期送出奖品数+奖品总数检查+每个人能能中的奖周期
$luckDraw->activityDate(ExampleConfig::$activityDate)
    ->timeRegionLimit(ExampleConfig::$timeRegion)
    ->prizePersonalLimit(ExampleConfig::$everyGetPrizes,
        function ($name) {
            $count = Db::table('prize')->where(['name' => $name])->count();
            return $count;
        }, ['thr']
    )->datePrizesLimit(ExampleConfig::$prizeDateLimit,
        function ($prizeId) {
            $count = Db::table('prize')->where(['prize' => $prizeId])->count();
            return $count;
        })
    ->prizesRemaining(function ($prizeId) {
        $count = Db::table('prize')->where(['prize' => $prizeId])->count();
        return $count;
    })
    ->userCanPrize(ExampleConfig::$userCanPrize,
        ExampleConfig::$activityBeginDate,
        ExampleConfig::$repeatData,
        function ($prizeId, $cycle) {
            $count = Db::table('prize')->where(['prize'=>$prizeId,'cycle'=>$cycle])->count();
            return $count;
        }
    )
    ->run();
var_dump($luckDraw->prizeId);