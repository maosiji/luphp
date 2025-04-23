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

use MAOSIJI\LUPHP\original\LUTime;

// 计算时间差
$beginTime = time();
$endTime = strtotime("2025-08-15");
//$endTime = time();
$maoTime = new LUTime();
$timeDiff = $maoTime->calculate_timediff($beginTime, $endTime);
print_r($timeDiff);
