# Lottery PHP抽奖模块
### 配置信息
* 数组  
```
说明:配置数据为Data，但是可以传入文件路径和数组
    [
        'activity_date' => ['2017-02-01 10:20:30'(项目开始日期), '2017-02-07 15:45:55'(项目结束日期)],
                  //开启CPAD功能必须设置
        'times_region' => [['09:10:22'(第一时间段开始时间), '10:22:31'(第一时间段结束时间)], ['11:01:22', '13:45:33']],
                  //开启CTPR功能必须设置，可多个时间段，格式时分秒
        'pre_section' => [1,10, 20, 30, 39],
                  //必须设置，奖品概率，依次为1等奖，2等奖，3等奖，4等奖，5等奖           
        'prize_count' => [1, 100, 200, 1000, 10000],
                  //每个奖品的总数量，开启CPC功能必须设置
        'repeat_data' => [1800, 1800, 1800, 1800, 1800],
                  //每个奖品的刷新时间，开启CUCP功能必须设置
        'user_can_prize' => [1, 1, 1, 1, 1],
                  //每个奖品当前用户可抽的次数，开启CUCP功能必须设置
        'every_prize_count' => 1,
                  //每个用户能中的奖的总数
        'prize_date_limit' => [
            'type'=>1或2,
            'data'=>[ 
                '2017-1-17'(日期:年月日) => [10(1等奖), 20(2等奖), 30(3等奖),40(4等奖),50(5等奖)],
                '2017-1-18' => [20, 40, 60,80,100]
            ]
            //每天每个奖品可送出奖品列表，开启CTGPL功能必须开启
            //type=1,表示检查每天可送出奖品数，data的key值为2017-1-17：type=2,表示检查每天时间段可送出奖品数，key值变为'2017-01-17 08:10:11|2017-01-17 12:00:25'
        ],
         'every_one_prize_event' => function () {
            //检查用户所中的奖的数量是否达到设定的最大值,这里返回数据库查询的匿名函数，需返回int数量值,或true(达到)|false
         },
         'prize_count_event' => function ($level) {
            //检查所中的奖是否已经全部被中完，进行数据库查询，返回int数量值，$level即奖品等级
         },
         'date_get_prizes_limit_event' => function ($level) {
            //检查每天可送出奖品是否到达最大值,进行数据库查询,返回int数量值，$level即奖品等级
         },
         'user_can_prize_event' => function ($level, $cycle) {
            //检查是否到达刷新期，用户可以再抽奖，进行数据库查询,返回int数量值，$level即奖品等级,$cycle为int(周期)，根据项目开始日期和奖品的刷新间隔计算
         }
];
```
### 接口
###### 1. __construct($config = []) 构造函数
```   
  $config = 可选,可以输入配置数组或配置路径，默认使用包内配置文件
```
###### 2. activityDate($dateRegion = []) 检查项目是否结束
```   
  $dateRegion = 可选,项目的开始和结束日期
```
###### 3. timesPermitRegion($timesRegion = []) 判断是否在每天的允许的抽奖时间段内
```   
  $timesRegion = 可选,每天允许抽奖的时间段
```
###### 4. everyOnePrizeCount($limit = -1,$params=[]) 判断个人中奖数是否到达设定值
```   
  $limit = 可选,可以输入配置数组或配置路径，默认使用包内配置文件
  $params = 可选,调用事件方法时输入的参数
```
###### 5. lottery($preSection = []) 开始抽奖(必须)
```   
  $preSection = 可选,奖品概率数组
```
###### 6. prizeCount($prizeCount = []) 检查奖品是否已经中完
```   
  $prizeCount = 可选,奖品总数列表
```
###### 7. dateGetPrizesLimit($prizeLimit = []) 检查当前所能送出的奖品的个数
```   
  $prizeLimit = 可选,每天的奖品送出个数
```
###### 8. userCanPrize($userCanPrize = [], $beginDate = '', $repeatDate = '') 用户是否过了刷新期，可以再抽奖
```   
  $userCanPrize = 可选,
  $beginDate = 可选,项目开始日期:'2017-02-22 10:10:10'
  $repeatDate = 可选,刷新时间:1800(单位小时) 
```
### 附录
* 执行状态
```
说明:功能执行状态通过 LuckyDraw->status 来确定
    值：
      LuckyDraw::PARAMS_NULL             所需参数为空
      LuckyDraw::ACTIVITY_OUT            活动结束或还没开始
      LuckyDraw::NOT_LOTTERY_TIME_REGION 现时间段不能抽奖
      LuckyDraw::PRIZE_TOTAL_LIMIT_REACH 每个人中奖数已到
      LuckyDraw::TOTAL_PRE_ERROR         抽奖概率错误
      LuckyDraw::THIS_PRIZE_OUT          此奖品已没有库存
      LuckyDraw::DATE_PRIZES_LIMIT       当前奖品的送出数量，在当天已送完
      LuckyDraw::USER_CAN_NOT_PRIZE_NOW  当前用户没到刷新期，当前刷新期已不能再抽奖
      LuckyDraw::EVENT_NOT_EXIST         事件不存在
      LuckyDraw::EVENT_RETURN_ERROR      事件返回值不合格式
```
### 使用例子
```
        $setting = './Config.php' //文件路径
        //$setting = [...]        //配置数组
        $LuckyDraw = new LuckyDraw($setting);   //创建实例
        $LuckyDraw->activityDate([...])
            ->timesPermitRegion(['...'])
            ->everyOnePrizeCount(1)
            ->lottery([...])
            ->prizeCount([...])
            ->dateGetPrizesLimit([...])
            ->userCanPrize([...]);
        $level = $LuckyDraw->level;
        $status = $LuckyDraw->status;
        $cycle = $LuckyDraw->cycle;
```
