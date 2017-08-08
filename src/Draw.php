<?php
namespace LuckyDraw;

/**
 * $draw = new Draw();
 *
 * $draw->addFilter(function () {
 *     // do something
 * });
 *
 * $draw->setPrize([5, 5, 15, 25, 40]);
 *
 * $draw->getPrize();
 *
 * Class Draw
 * @package Lottery
 */
class Draw
{
    protected $prizes = [];

    /**
     * @param $prizes
     */
    public function setPrizes($prizes)
    {
        // probability 中奖率
        // number 奖品剩余数量
        $prizes = is_array($prizes)
            ? $prizes
            : func_get_args();

        foreach ($prizes as $params) {
            $this->setPrize(...$params);
        }
    }

    /**
     * 设置奖品中奖率
     *
     * @param $prize
     * @param $probability
     * @param null $number
     * @return self
     */
    public function setPrize($prize, $probability, $number = null)
    {
        // 如果奖品数量为0,中奖率为0
        if ($number === 0) {
            $probability = 0;
        }

        $this->prizes[$prize] = $probability;

        return $this;
    }

    /**
     * @return bool|int|string
     */
    public function getPrize()
    {
        $probability = array_sum($this->prizes);

        if (!$this->prizes || $probability === 0) {
            return false;
        }

        if (array_sum($this->prizes) > 100) {
            throw new \RuntimeException("奖品中奖率总和不能超过100");
        }

        if (strpos((string) $probability, ".")) {
            list($head, $tail) = explode('.', (string) $probability, 2);
            $totalNumber = 100 * pow(10, strlen($tail));
            $probability = (int) ($head . $tail);
        } else {
            $totalNumber = 100;
        }

        $slot = rand(1, $totalNumber);
        // 如果随机数字大于中奖率,判断为中奖
        if ($slot > $probability) {
            return false;
        }
        // 按照中奖率对奖品进行倒序排序
        arsort($this->prizes);
        // 取出奖品
        foreach ($this->prizes as $prize => $pro) {
            if ($pro < $slot) {
                return $prize;
            }
        }
    }
}