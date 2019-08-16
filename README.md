Toolkits
=======
支持:

- excel
- html2word
- jwt
- mail
- sms
- str
- time
- 中文转拼音

安装
------------

```bash
composer require cshaptx4869/utils
```

实例
-------

###### 详见 run.php

```php
<?php

require_once 'vendor/autoload.php';

use Fairy\Excel;
use Fairy\MhtFileMaker;
use Fairy\Pinyin;
use Fairy\SmsSender;
use Fairy\SMTPMailSender;
use Fairy\Str;
use Fairy\Time;
use Fairy\Token;

class Run
{
    // ...
}

$run = new Run();
echo $run->jwtEncode();
echo $run->jwtDecode();
echo $run->html2word();
echo $run->excelExport();
echo $run->praseExcel();
echo $run->sendMail();
echo $run->sendSms();
// ...
```

## 静态类库

以下类库都在`Fariy`命名空间下

## Pinyin

> 中文转拼音

```
// 获取拼音
Pinyin::getPinyin("早上好")

// 获取拼音缩写
Pinyin::getShortPinyin("早上好")

```

## Str

> 字符串操作

```
// 检查字符串中是否包含某些字符串
Str::contains($haystack, $needles)

// 检查字符串是否以某些字符串结尾
Str::endsWith($haystack, $needles)

// 获取指定长度的随机字母数字组合的字符串
Str::random($length = 16)

// 字符串转小写
Str::lower($value)

// 字符串转大写
Str::upper($value)

// 获取字符串的长度
Str::length($value)

// 截取字符串
Str::substr($string, $start, $length = null)

```

## Time

> 时间戳操作

```
// 今日开始和结束的时间戳
Time::today();

// 昨日开始和结束的时间戳
Time::yesterday();

// 本周开始和结束的时间戳
Time::week();

// 上周开始和结束的时间戳
Time::lastWeek();

// 本月开始和结束的时间戳
Time::month();

// 上月开始和结束的时间戳
Time::lastMonth();

// 今年开始和结束的时间戳
Time::year();

// 去年开始和结束的时间戳
Time::lastYear();

// 获取7天前零点到现在的时间戳
Time::dayToNow(7)

// 获取7天前零点到昨日结束的时间戳
Time::dayToNow(7, true)

// 获取7天前的时间戳
Time::daysAgo(7)

//  获取7天后的时间戳
Time::daysAfter(7)

// 天数转换成秒数
Time::daysToSecond(5)

// 周数转换成秒数
Time::weekToSecond(5)

```

