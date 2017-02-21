<?php
namespace LuckyDraw\Core;

use ExtractCompress\ExtractCompress;
use LuckyDraw\StatusSpecification\LuckyDrawStatusSpecification;

class LuckyDraw
{
    private $statusType = 0;
    private $level = -1;//奖品抽奖
    private $cycle = 0;//周期
    private $pdo = NULL;//pdo设置信息
    private $sqlArr = NULL;//数据库信息
    private $pdoInstance = NULL;//pdo实例

    public function run($lottery)
    {
        if (!empty($lottery)) {
            $funcList = $lottery['func'];
            $this->pdo = $lottery['pdo'];
            $this->sqlArr = $lottery['sql'];
            unset($lottery['pdo']);
            unset($lottery['func']);
            unset($lottery['sql']);
            $level = 0;
            $result = true;
            (!$result || !$funcList['CPAD']) or $result = $this->checkProjectActivityDate($lottery['activityDate']);
            (!$result || !$funcList['CTPR']) or $result = $this->checkTimesPermitRegion($lottery['timesRegion']);
            (!$result || !$funcList['EOP']) or $result = $this->everyOnePrize();
            !$result or $result = $this->lottery($lottery['preSection']);
            if ($this->level !== -1) {
                (!$result || !$funcList['CTGPL']) or $result = $this->checkTimeGetPrizesLimit($lottery['prizeDateLimit']);
                (!$result || !$funcList['CPC']) or $result = $this->checkPrizeCount($lottery['prizeCount']);
                (!$result || !$funcList['CUCP']) or $this->cycle = $this->getCycle($lottery['activityDate'][0], $lottery['repeatData'][$this->level - 1]);
                (!$result || !$funcList['CUCP']) or $result = $this->checkUserCanPrize($this->cycle, $lottery['userCanPrize']);
            } else
                $level = false;
            (!$result) ? $level = !isset($lottery['sparePrizeId']) ? false : $lottery['sparePrizeId'] : $level = $this->level;
            unset($this->pdoInstance);
            return $level;
        } else {
            $this->statusType = LuckyDrawStatusSpecification::SETTING_NULL;
            return false;
        }
    }

    /**
     *
     * @param $dateRegion
     * @return bool
     */
    private function checkProjectActivityDate($dateRegion)
    {
        $result = true;
        if (empty($dateRegion)) {
            $this->statusType = LuckyDrawStatusSpecification::FUNC_PARAMS_NULL;
            $result = false;
        } else {
            $nowTime = $_SERVER['REQUEST_TIME'];
            if ($nowTime < strtotime($dateRegion[0]) || $nowTime > strtotime($dateRegion[1])) {
                $this->statusType = LuckyDrawStatusSpecification::ACTIVITY_STOP;
                $result = false;
            }
        }
        return $result;
    }

    /**
     * 检查是否在可抽奖时间段
     * @param $timesRegion
     * @return bool
     */
    private function checkTimesPermitRegion($timesRegion)
    {
        $result = false;
        if (!empty($timesRegion)) {
            $nowTimeStamp = $_SERVER['REQUEST_TIME'];
            $ymd = date('Y-m-d', $_SERVER['REQUEST_TIME']);
            foreach ($timesRegion as $values) {
                if ($nowTimeStamp > strtotime($ymd . $values[0])
                    && $nowTimeStamp < strtotime($ymd . $values[1])
                ) {
                    $result = true;
                    break;
                }
            }
            if ($result === false)
                $this->statusType = LuckyDrawStatusSpecification::TIMES_PERMIT;
        } else
            $this->statusType = LuckyDrawStatusSpecification::FUNC_PARAMS_NULL;
        return $result;
    }

    /**
     * 每个人只能中一次奖
     * @return bool
     */
    public function everyOnePrize()
    {
        $result = true;
        $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['userCondition'];
        $result = $this->sqlCount($countSql);
        if ($result === false) {
            $this->statusType = LuckyDrawStatusSpecification::ERR_SQLERR;
            $result = false;
        }
        if ($result >= 1) {
            $this->statusType = LuckyDrawStatusSpecification::ONEPRIZE_ONLY;
            $result = false;
        }
        if ($result === 0)
            $result = true;
        return $result;
    }

    /**
     * 进行抽奖
     * @param $preSection
     * @return bool
     */
    public function lottery($preSection)
    {
        $result = true;
        if (empty($preSection)) {
            $this->statusType = LuckyDrawStatusSpecification::FUNC_PARAMS_NULL;
            $result = false;
        } else {
            $preSection = $this->makePreSection($preSection, $preSum);
            if (is_int($preSum) && $preSum >= 1) {
                $randNum = rand(1, $preSum);
                $size = sizeof($preSection);
                $prizeLevel = 0;
                for ($i = 0; $i < $size; $i++) {
                    if (($randNum == $preSection[$i][0])
                        || ($randNum == $preSection[$i][1])
                        || ($randNum > $preSection[$i][0] && $randNum < $preSection[$i][1])
                    ) {
                        $prizeLevel = $i + 1;
                        break;
                    }
                }
                $this->level = $prizeLevel;
            } else {
                $this->statusType = LuckyDrawStatusSpecification::PROBABILITY_VALUE_ERROR;
                $result = false;
            }
        }
        return $result;
    }

    private function makePreSection($pre, &$preSum)
    {
        $preixSum = 0;
        $nextSum = 0;
        $preSection = [];
        foreach ($pre as $values) {
            $nextSum += $values;
            $preSection[] = [$preixSum + 1, $nextSum];
            $preixSum = $nextSum;
        }
        $preSum = $nextSum;
        return $preSection;
    }

    /**
     * 检查是否需要现在奖品数，检查是否需要刷新奖品周期
     * @param $prizeCount
     * @return bool
     */
    public function checkPrizeCount($prizeCount)
    {
        $result = true;
        if (empty($prizeCount)) {
            $this->statusType = LuckyDrawStatusSpecification::FUNC_PARAMS_NULL;
            $result = false;
        } else {
            $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['levelCondition'];
            $levelCount = $this->sqlCount($countSql . $this->level);
            if ($levelCount === false)
                $result = false;
            else {
                $levelIndex = $this->level - 1;
                if ($prizeCount[$levelIndex] <= $levelCount) { //如果当前奖品被抽完
                    $this->statusType = LuckyDrawStatusSpecification::PRIZE_OUT;
                    $result = false;
                }
            }
        }
        return $result;
    }

    /**
     * 检查所中奖项是否已超过当天额度
     * @param $prizeLimitArray
     * @return bool
     */
    public function checkTimeGetPrizesLimit($prizeLimitArray)
    {
        $result = true;
        if (empty($prizeLimitArray)) {
            $this->statusType = LuckyDrawStatusSpecification::FUNC_PARAMS_NULL;
            $result = false;
        } else {
            $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['levelCondition'] . $this->level;
            $limitCount = $prizeLimitArray[date('Y-m-d', $_SERVER['REQUEST_TIME'])][$this->level - 1];
            $count = $this->sqlCount($countSql);
            if ($count === false || $count >= $limitCount) {
                $this->statusType = LuckyDrawStatusSpecification::NOW_THIS_PRIZE_CAN_NOT_GET;
                $result = false;
            }
        }
        return $result;
    }

    /**
     * 检查是否超过自己再中奖的条件
     * @param $cycle
     * @param $userCanPrize
     * @return bool
     */
    public function checkUserCanPrize($cycle, $userCanPrize)
    {
        $result = true;
        if (!isset($cycle) || !isset($userCanPrize)) {
            $this->statusType = LuckyDrawStatusSpecification::FUNC_PARAMS_NULL;
            $result = false;
        } else {
            $count = 0;
            $limit = 0;
            $countSql = '';
            if (is_array($userCanPrize)) {
                $limit = $userCanPrize[$this->level - 1];
                $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['levelCondition'] . $this->level
                    . ' and ' . $this->sqlArr['userCondition']
                    . ' and ' . $this->sqlArr['cycleCondition'] . $this->cycle;
            } else {
                $limit = $userCanPrize;
                $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['userCondition']
                    . ' and ' . $this->sqlArr['cycleCondition'] . $this->cycle;
            }
            $count = $this->sqlCount($countSql);
            if ($count >= $limit) {
                $this->statusType = LuckyDrawStatusSpecification::REPEAT_LIMIT;
                $result = false;
            }
        }
        return $result;
    }

    /**
     * 获取周期
     * @param $beginDate    项目开始时间
     * @param $repeatData   中奖次数刷新时间
     * @return int  返回周期,（ 为当前时间 - 开始时间 ）/ 刷新周期时间
     */
    private function getCycle($beginDate, $repeatData)
    {
        $beginStamp = strtotime($beginDate);
        $second = time() - $beginStamp;
        $cycle = (int)((int)($second / 3600) / $repeatData) + 1;
        return $cycle;
    }

    /**
     * 查数据的个数
     * @param $sqlString
     * @return array|bool|null
     */
    private function sqlCount($sqlString)
    {
        $result = null;
        try {
            if (empty($this->pdoInstance))
                $this->pdoInstance = new \PDO($this->pdo['dsn'], $this->pdo['user'], $this->pdo['passWord']);
            $pdoSearchResult = $this->pdoInstance->query($sqlString);
            if (!$pdoSearchResult) {
                $this->statusType = LuckyDrawStatusSpecification::PDO_EXECUE_ERROR;
                $result = false;
            } else {
                $rows = $pdoSearchResult->fetch();
                $result = (int)$rows[0];
            }
        } catch (\PDOException $e) {
            $this->statusType = LuckyDrawStatusSpecification::PDO_EXECUE_ERROR;
            return false;
        }
        return $result;
    }

    /**
     * 获取当前周期
     */
    public function getCycleData()
    {
        return $this->cycle;
    }

    /**
     * 获取结果码
     * @return int|string
     */
    public function getStatusType()
    {
        return $this->statusType;
    }
}