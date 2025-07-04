<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */
require __DIR__ . '/../vendor/autoload.php';

use MAOSIJI\LU\LUTime;

$maoTime = new LUTime();


// 计算时间差
$beginTime = time();
$endTime = strtotime("2025-08-15");
$timeDiff = $maoTime->calculate_timediff($beginTime, $endTime);
print_r($timeDiff);

// 计算年龄
$birthday = '2014-12-05';
$age = $maoTime->age($birthday);
print_r($age);

// 获取未来最近的整秒时间（戳）
$time = strtotime('2014-12-05 12:45:25');
$nextTime = $maoTime->get_next_full_second_time($time);
$nextTimestamp = $maoTime->get_next_full_second_timestamp($time);
print_r($nextTime);
print_r($nextTimestamp);

