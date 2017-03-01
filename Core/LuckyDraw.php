<?php
namespace LuckyDraw\Core;

use Ant\Http\Test\RequestTest;
use LuckyDraw\Exception\LuckyDrawException;
use think\Exception;

class LuckyDraw
{
    private $level = -1;//奖品抽奖
    private $cycle = 0;//周期
    private $pdo = NULL;//pdo设置信息
    private $sqlArr = NULL;//数据库信息
    private $pdoInstance = NULL;//pdo实例
    private $exceptionMssage = '';//错误异常消息
    private $exceptionCode = 0;//错误异常码

    public function run($lottery)
    {
        try {
            if (!empty($lottery)) {
                $funcList = $lottery['func'];
                $this->pdo = $lottery['pdo'];
                $this->sqlArr = $lottery['sql'];
                unset($lottery['pdo']);
                unset($lottery['func']);
                unset($lottery['sql']);
                $level = 0;
                !$funcList['CPAD'] or $this->checkProjectActivityDate($lottery['activityDate']);
                !$funcList['CTPR'] or $this->checkTimesPermitRegion($lottery['timesRegion']);
                !$funcList['EOP'] or $this->everyOnePrize();
                $this->lottery($lottery['preSection']);
                !$funcList['CTGPL'] or $this->checkTimeGetPrizesLimit($lottery['prizeDateLimit']);
                !$funcList['CPC'] or $this->checkPrizeCount($lottery['prizeCount']);
                !$funcList['CUCP'] or $this->getCycle($lottery['activityDate'][0], $lottery['repeatData'][$this->level - 1]);
                !$funcList['CUCP'] or $this->checkUserCanPrize($this->cycle, $lottery['userCanPrize']);
                if ($this->level >= 1)
                    $level = $this->level;
                else
                    $level = !isset($lottery['sparePrizeId']) ? $lottery['sparePrizeId'] : false;
                unset($this->pdoInstance);
                return $level;
            } else
                throw new LuckyDrawException('抽奖输入参数不能为空', LuckyDrawException::PARAMS_NULL);
        } catch (\ErrorException $e) {
            $this->exceptionMssage = $e->getMessage();
            $this->exceptionCode = $e->getCode();
            return false;
        }
    }

    /**
     * 检查活动日期限制
     * @param $dateRegion
     * @return bool
     * @throws LuckyDrawException
     */
    private function checkProjectActivityDate($dateRegion)
    {
        $result = true;
        if (empty($dateRegion))
            throw new LuckyDrawException('检查活动日期需要的参数为空', LuckyDrawException::PARAMS_NULL);
        else {
            $nowTime = $_SERVER['REQUEST_TIME'];
            if ($nowTime < strtotime($dateRegion[0]) || $nowTime > strtotime($dateRegion[1]))
                throw new LuckyDrawException('活动结束', LuckyDrawException::ACTIVITY_OUT);
        }
        return $result;
    }

    /**
     * 检查是否在可抽奖时间段
     * @param $timesRegion
     * @return bool
     * @throws LuckyDrawException
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
            if (!$result)
                throw new LuckyDrawException('此时间段不能进行抽奖', LuckyDrawException::NOT_LOTTERY_TIME_REGION);
        } else
            throw new LuckyDrawException('检查抽奖时间段的输入参数不能为空', LuckyDrawException::PARAMS_NULL);
    }

    /**
     * 每个人只能中一次奖
     * @throws LuckyDrawException
     */
    public function everyOnePrize()
    {
        $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['userCondition'];
        $result = $this->sqlCount($countSql);
        if ($result !== 0)
            throw new LuckyDrawException('检查每个人只能中一次奖 ', LuckyDrawException::EVERYONE_ONE_PRIZE);
    }

    /**
     * 进行抽奖
     * @param $preSection
     * @throws LuckyDrawException
     */
    public function lottery($preSection)
    {
        if (empty($preSection))
            throw new LuckyDrawException('抽奖概率不能为空', LuckyDrawException::PARAMS_NULL);
        else {
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
            } else
                throw new LuckyDrawException('抽奖总概率错误', LuckyDrawException::TOTAL_PRE_ERROR);
        }
    }

    /**
     * 创建概率区间
     * @param $pre
     * @param $preSum
     * @return array
     */
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
     * @throws LuckyDrawException
     */
    public function checkPrizeCount($prizeCount)
    {
        if (empty($prizeCount)) {
            throw new LuckyDrawException('奖品总数参数为空', LuckyDrawException::PARAMS_NULL);
        } else {
            $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['levelCondition'];
            $levelCount = $this->sqlCount($countSql . $this->level);
            $levelIndex = $this->level - 1;
            if ($prizeCount[$levelIndex] <= $levelCount)
                throw new LuckyDrawException("第{$this->level}奖品被抽完", LuckyDrawException::THIS_PRIZE_OUT);
        }
    }

    /**
     * 检查所中奖项是否已超过当天额度
     * @param $prizeLimitArray
     * @return bool
     * @throws LuckyDrawException
     */
    public function checkTimeGetPrizesLimit($prizeLimitArray)
    {
        if (empty($prizeLimitArray))
            throw new LuckyDrawException('奖品送出限制参数为空', LuckyDrawException::PARAMS_NULL);
        else {
            $countSql = $this->sqlArr['origin'] . ' ' . $this->sqlArr['levelCondition'] . $this->level;
            $limitCount = -1;
            if ($prizeLimitArray['type'] == 1) {
                $limitCount = $prizeLimitArray['data'][date('Y-m-d', $_SERVER['REQUEST_TIME'])][$this->level - 1];
            } else {
                $data = $prizeLimitArray['data'];
                $day = date('Y-m-d', $_SERVER['REQUEST_TIME']);
                foreach ($data as $key => $values) {
                    $region = explode('|', $key);
                    if (sizeof($region) < 2)
                        continue;
                    $begin = strtotime($region[0]);
                    $end = strtotime($region[1]);
                    if ($begin === false || $end === false)
                        continue;
                    $thisDay = date('Y-m-d', $begin);
                    if ($thisDay != $day)
                        continue;
                    elseif ($_SERVER['REQUEST_TIME'] > $begin && $_SERVER['REQUEST_TIME'] < $end) {
                        $limitCount = $data[$this->level - 1];
                        break;
                    } else
                        continue;
                }
            }
            if ($limitCount === -1)
                throw new LuckyDrawException('当前时间段不能抽奖', LuckyDrawException::NOT_LOTTERY_TIME_REGION);
            $count = $this->sqlCount($countSql);
            if ($count === false || $count >= $limitCount)
                throw new LuckyDrawException('奖品总数参数为空', LuckyDrawException::PARAMS_NULL);
        }
    }

    /**
     * 检查是否超过自己再中奖的条件
     * @param $cycle
     * @param $userCanPrize
     * @throws LuckyDrawException
     */
    public function checkUserCanPrize($cycle, $userCanPrize)
    {
        if (!isset($cycle) || !isset($userCanPrize))
            throw new LuckyDrawException('每人能中奖品个数输入参数为空', LuckyDrawException::PARAMS_NULL);
        else {
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
            if ($count >= $limit)
                throw new LuckyDrawException('当前奖品个人所能中的奖品数已满', LuckyDrawException::USER_CAN_NOT_PRIZE);
        }
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
     * @return int|null
     * @throws \Exception
     */
    private function sqlCount($sqlString)
    {
        $result = null;
        try {
            if (empty($this->pdoInstance))
                $this->pdoInstance = new \PDO($this->pdo['dsn'], $this->pdo['user'], $this->pdo['passWord']);
            $pdoSearchResult = $this->pdoInstance->query($sqlString);
            if (!$pdoSearchResult)
                throw new LuckyDrawException('数据库查询错误', LuckyDrawException::PDO_QUERY_ERROR);
            else {
                $rows = $pdoSearchResult->fetch();
                $result = (int)$rows[0];
                return $result;
            }
        } catch (\ErrorException $e) {
            throw $e;
        }
    }

    /**
     * 获取当前周期
     */
    public function getCycleData()
    {
        return $this->cycle;
    }
}