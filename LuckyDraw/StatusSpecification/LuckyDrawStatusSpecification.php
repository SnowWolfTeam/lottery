<?php
namespace LuckyDraw\StatusSpecification;
class LuckyDrawStatusSpecification
{
    const ACTIVITY_STOP = 0x81;//活动结束
    const TIMES_PERMIT = 0x82;//此时间段不能抽奖
    const PRIZE_OUT = 0x83;//奖品抽完
    const ONEPRIZE_ONLY = 0x84;//每人只能中奖一次
    const ERR_SQLERR = 0x85;//数据库配置错误
    const SETTING_NULL = 0x86;//构造时传入的配置参数为空
    const REPEAT_LIMIT = 0x87;//当前周期你不能再抽奖
    const BEGINDATE_NULL = 0x88;//使用刷新功能时项目开始日期不能为空
    const PROBABILITY_VALUE_ERROR =0x89;//概率总数出错
    const NOW_THIS_PRIZE_CAN_NOT_GET =0x90;//当前所中的奖项当天的可送出额已满
    const FUNC_PARAMS_NULL = 0x91;//函数传入的参数为空
    const EVERYDAY_PRIZE_LIMIT_ERROR = 0x92;//当前中奖id在每天送出奖品列表中不存在

    private static $msg = [
        0x81 => "活动结束",
        0x82 => "此时间段不能抽奖",
        0x83 => "奖品抽完",
        0x84 => "每人当前只能中奖一次",
        0x85 => "数据库配置错误",
        0x86 => "配置信息没有保存到缓存，且没有通过参数传进来",
        0x87 => "当前时间周期你不能再抽奖",
        0x88 => "使用刷新功能时项目开始日期不能为空",
        0x89 => "所设置的概率总数与概率数组的数值总和不一样",
        0x90 => "当前实例运行所得奖项在当天可送出的额度已满",
        0x91 => "函数必要参数输入为空",
        0x92 => "当前实例运行所得奖项在不在当天可送出列表中"
    ];

    /**
     * 获取对应状态吗的意思说明
     * @param $octonaryNumber
     * @return mixed
     */
    public static function getMsgFromStatus($octonaryNumber){
        return self::$msg[$octonaryNumber];
    }
}