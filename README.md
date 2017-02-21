# Lottery PHP抽奖模块
### 配置信息
* 数组    
```
[
    'func' => [
        'CPAD' => true, //开启验证项目日期功能,可选
        'CTPR' => true, //开启验证每天抽奖时间段功能，可选
        'EOP' => true,  //开启每个用户只能抽中一次奖品的功能，可选
        'CTGPL' => true,//开启每天每个奖品可送出个数验证功能，可选
        'CPC' => true,  //开启验证奖品是否已经抽完的功能，可选
        'CUCP' => true  //开启每个用户每个奖品可以抽取个数的功能,可选
    ],
    'activityDate' => ['2017-02-01 10:20:30'(项目开始日期), '2017-02-07 15:45:55'(项目结束日期)],
                  //开启CPAD功能必须设置
    'timesRegion' => [['09:10:22'(第一时间段开始时间), '10:22:31'(第一时间段结束时间)], ['11:01:22', '13:45:33']],
                  //开启CTPR功能必须设置，可多个时间段，格式时分秒
    'pdo' => ['dsn' => '', 'user' => 'mysql', 'passWord' => '1234'],
                  //开启EOP或CTGPL或CPC或CUCP功能必须设置，pdo参数
    'preSection' => [1,10, 20, 30, 39],
                  //必须设置，奖品概率，依次为1等奖，2等奖，3等奖，4等奖，5等奖
    'sparePrizeId' => 5,               
                  //可选设置，代表5等奖，假如5等奖是安慰奖，那么模块在某些情况下没有可中的奖品就会以5等奖返回结果
    'prizeCount' => [1, 100, 200, 1000, 10000],
                  //每个奖品的总数量，开启CPC功能必须设置
    'repeatData' => [1800, 1800, 1800, 1800, 1800],
                  //每个奖品的刷新时间，开启CUCP功能必须设置
    'userCanPrize' => [1, 1, 1, 1, 1],
                  //每个奖品当前用户可抽的次数，开启CUCP功能必须设置
    'prizeDateLimit' => [
        '2017-1-17'(日期:年月日) => [10(1等奖), 20(2等奖), 30(3等奖),40(4等奖),50(5等奖)],
        '2017-1-18' => [20, 40, 60,80,100]
                  //每天每个奖品可送出奖品列表，开启CTGPL功能必须开启
    ],
    'spl' => [   
        'origin' => 'select count(*) from 你的表名 where= ',
        'levelCondition' => '你的奖品字段=',
        'userCondition' => '你的关键字段=关键字段内容',
        'cycleCondition' => '你的周期字段=']
                  //数据库sql语句，需要pdo就必须设置
];
```
### 使用例子
```
        $setting = ?;                   //抽奖配置数组
        $LuckyDraw = new LuckyDraw();   //创建实例
        $result = $LuckyDraw->run();    //进行抽奖
        var_dump($result);              //结果输出，如果中奖即返回对应奖品的中奖id，其他情况返回false
        LuckyDrawStatusSpecification::getMsgFromStatus($LuckyDraw->getStatusType());//获取运行结果的状态码的说明信息
```
