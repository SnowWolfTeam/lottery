<?php
namespace LuckyDraw\Core;

use SqlHelper\SqlHelper;

class LuckyDraw
{
    private $statusType = 0;
    private $setting = null;//配置内容
    private $level = -1;//奖品抽奖
    private $cycle = 0;//周期

    /**
     * LuckyDraw constructor.
     * @param array $setting
     *              [
     *                'projectTime'=>[ 'beginDate'=>开始时间,'endDate'=>结束时间 ],
     *                'timesPermit'=>[['beginTime'=>开始时分秒,'endTime'=>结束时分秒],
     *                'pdoSetting'=>['dsn'=>'','usr'=>数据库用户名,'passWord'=>数据库用户密码]],
     *                'prizePre'=>[ 1,10,20,30,39 ]总数为100%
     *                'preSum' => 100
     *                'sparePrizeId' =>5
     *                'everyPrizeCount'=>[1,100,200,1000,10000]每个奖品的数量
     *                'repeatData'=>[1800,1800,1800,1800,1800]每个奖品的刷新时间
     *                'userCanPrize'=>[1,1,1,1,1]每个奖品当前用户可抽的次数
     *                'preSection'=>概率区间，用于概率算法，自动生成
     *                'everyDayPrizeLimit'=>['2017-1-17'=>[10,20,30],
     *                                       '2017-1-18'=>[20,40,60],
     *                                       '2017-1-19'=>[30,60,90]]
     *              ]
     */
    public function __construct($setting)
    {
        if (!empty($setting)) {
            if (empty($setting['preSection'])) {
                $preixSum = 0;
                $nextSum = 0;
                $preSection = [];
                foreach ($setting['prizePre'] as $values) {
                    $nextSum += $values;
                    $preSection[] = [$preixSum, $nextSum];
                    $preixSum = $nextSum;
                }
                if (empty($setting['preSum']))
                    $setting['preSum'] = $nextSum;
                $setting['preSection'] = $preSection;
                unset($preSection);
                unset($setting['prizePre']);
                unset($preixSum);
                unset($nextSum);
            }
            $this->setting = $setting;
            unset($setting);
        } else
            $this->statusType = 0x86;
    }

    /**
     * 检查是否过了活动时间
     * @return $this|null
     */
    public function checkProjectDate()
    {
        if ($this->statusType === 0) {
            $nowTime = time();
            if ($nowTime < strtotime($this->setting['projectTime']['beginDate']) || $nowTime > strtotime($this->setting['projectTime']['endDate']))
                $this->statusType = 0x81;
            unset($nowTime);
        }
        return $this;
    }

    /**
     * 检查是否在可抽奖时间段
     * @return $this|null
     */
    public function checkTimesPermit()
    {
        if ($this->statusType === 0) {
            $nowTimeStamp = time();
            $ymd = date('Y-m-d', $nowTimeStamp);
            $result = false;
            foreach ($this->setting['timesPermit'] as $values) {
                if ($nowTimeStamp > strtotime($ymd . $values['beginTime'])
                    && $nowTimeStamp < strtotime($ymd . $values['endTime'])
                ) {
                    $result = true;
                    break;
                }
            }
            if (!$result)
                $this->statusType = 0x82;
            unset($this->setting['timesPermit']);
        }
        return $this;
    }

    /**
     * 每个人只能中一次奖
     * @return $this|null
     */
    public function everyOnePrize($sqlString)
    {
        if ($this->statusType === 0) {
            if (empty($sqlString))
                $this->statusType = 0x91;
            else {
                $result = $this->sqlCount($sqlString);
                if ($result === false)
                    $this->statusType = 0x85;
                else {
                    if ($result >= 1)
                        $this->statusType = 0x84;
                }
            }
        }
        return $this;
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
            $db = new \PDO($this->setting['pdoSetting']['dsn'], $this->setting['pdoSetting']['user'], $this->setting['pdoSetting']['passWord']);
            $pdoSearchResult = $db->query($sqlString);
            if (!$pdoSearchResult)
                $result = false;
            else {
                $rows = $pdoSearchResult->fetch();
                $db = null;
                $result = (int)$rows[0];
            }
            unset($db);
            unset($pdoSearchResult);
        } catch (\PDOException $e) {
            return false;
        }
        return $result;
    }


    /**
     * 进行抽奖
     */
    public function lottery($preCount = 0)
    {
        if ($this->statusType === 0) {
            $preSum = 1;
            if ($preCount > 0)
                $preSum = $preCount;
            else
                $preSum = $this->setting['preSum'];
            if (is_int($preSum) && $preSum >= 1) {
                $randNum = rand(1, $preSum);
                $size = sizeof($this->setting['prizePre']);
                $prizeLevel = 0;
                for ($i = 0; $i < $size; $i++) {
                    if ($randNum > $this->setting['preSection'][$i][0] && $randNum <= $this->setting['preSection'][$i][1]) {
                        $prizeLevel = $i + 1;
                        break;
                    }
                }
                $this->level = $prizeLevel;
            } else
                $this->statusType = 0x89;
        }
        return $this;
    }

    /**
     * 修改配置信息
     * @param $setting
     */
    public function changeSettingArray($setting)
    {
        $this->setting = array_merge($this->setting, $setting);
        unset($setting);
    }

    /**
     * 检查是否需要现在奖品数，检查是否需要刷新奖品周期
     * @param string $cachePrefix 缓存前缀
     * @return $this
     */
    public function checkPrizeCount($countSql)
    {
        if ($this->statusType === 0 && $this->level != -1) {
            if (!empty($countSql)) {
                $levelCount = $this->sqlCount($countSql . $this->level);
                if ($levelCount === false)
                    $this->statusType = 0x85;
                else {
                    $levelIndex = $this->level - 1;
                    if (!empty($this->setting['sparePrizeId'])) {
                        if ($this->setting['sparePrizeId'] == $this->level)
                            return $this;
                    }

                    if ($this->setting['everyPrizeCount'][$levelIndex] <= $levelCount) { //如果当前奖品被抽完
                        $size = sizeof($this->setting['everyPrizeCount']);
                        $count = 0;
                        $result = false;

                        for ($i = $size - 1; $i >= 0; $i--) {
                            if ($i != $levelIndex) {
                                $count = $this->sqlCount($countSql . $this->level);
                                if ($count < $this->setting['everyPrizeCount'][$i]) {
                                    $result = $i + 1;
                                    break;
                                }
                            }
                        }

                        if ($result === false) {
                            if (!empty($this->setting['sparePrizeId']))
                                $this->level = (int)$this->setting['sparePrizeId'];
                            $this->statusType = 0x83;
                        } else
                            $this->level = $result;
                    }
                }
            } else
                $this->statusType = 0x91;
        }
        return $this;
    }

    /**
     * 检查是否超过自己再中奖的条件
     */
    public function checkUserCanPrize($countSql)
    {
        if ($this->statusType === 0 && $this->level != -1) {
            if (!empty($this->setting['userCanPrize'])) {
                if (empty($this->setting['projectTime']['beginDate'])) {
                    if (!empty($this->setting['sparePrizeId']))
                        $this->level = $this->setting['sparePrizeId'];
                    $this->statusType = 0x88;
                    return $this;
                }

                $count = 0;
                $countSql = str_replace('#1', $this->level, $countSql);
                if (!empty($this->setting['repeatData']))
                    $countSql = str_replace('#2', $this->cycle, $countSql);
                $count = $this->sqlCount($countSql);

                if ($count == $this->setting['userCanPrize'][$this->level - 1]) {
                    if (!empty($this->setting['sparePrizeId']))
                        $this->level = $this->setting['sparePrizeId'];
                    $this->statusType = 0x87;
                }
            }
        }
        return $this;
    }

    /**
     * 检查所中奖项是否已超过当天额度
     * @param array $prizeLimitArray
     */
    public function checkTimeGetPrizesLimit($countSql, $prizeLimitArray = [])
    {
        if ($this->statusType === 0 && $this->level != -1) {
            if (empty($countSql))
                $this->statusType = 0x91;
            else {
                $limitCount = $prizeLimitArray[date('Y-m-d', time())][$this->level - 1];
                if ($limitCount !== 0 && empty($limitCount))
                    $this->statusType = 0x92;
                else {
                    $countSql = str_replace('#1', $this->level, $countSql);
                    $count = $this->sqlCount($countSql);
                    if ($count === false)
                        $this->statusType = 0x85;
                    else {
                        if ($count >= $limitCount) {
                            $this->statusType = 0x90;
                            if (!empty($this->setting['sparePrizeId']))
                                $this->level = $this->setting['sparePrizeId'];
                        }
                    }
                }
            }
        }
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

    /**
     * 获取抽奖结果
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
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
}