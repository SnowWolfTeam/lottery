<?php
namespace LuckyDraw\Exception;

use Exception;

class LuckyDrawException extends Exception
{
    const PARAMS_NULL = 0x1000;
    const PDO_SQL_ERROR = 0x1001;
    const ACTIVITY_OUT = 0x1100;
    const NOT_LOTTERY_TIME_REGION = 0x1101;
    const EVERYONE_ONE_PRIZE = 0x1102;
    const TOTAL_PRE_ERROR = 0x1103;
    const THIS_PRIZE_OUT = 0x1104;
    const USER_CAN_NOT_PRIZE = 0x1105;
    const PDO_QUERY_ERROR = 0x1106;

    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }

    public function __toString()
    {
        return __CLASS__ . ":[Code:$this->code][{Line:$this->line}]: {$this->message}\n";
    }
}