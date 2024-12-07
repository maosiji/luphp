<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-07 20:03
 * update               : 
 * project              : library-luphp
 */
require __DIR__ . '/../src/LURandom.php';

use MAOSIJI\luphp\LURandom;

$r = new LURandom();

echo PHP_EOL . '生成6位字符串，其中首位不为0' . PHP_EOL;
echo $r->getRandNumberByMtRand(6) . PHP_EOL;
echo $r->getRandStrByStrShuffle(6, array('is_first_not'=>'0', 'type'=>array('number'))) . ' - getRandStrByStrShuffle ' . PHP_EOL;
echo $r->getRandStrByShuffle(6, array('is_first_not'=>'0', 'type'=>array('number'))) . ' - getRandStrByShuffle ' . PHP_EOL;

echo PHP_EOL . '生成6位奇数' . PHP_EOL;
echo $r->getRandStrByStrShuffle(6, array('custom'=>'13579', 'custom_type'=>'override')) . ' - getRandStrByStrShuffle ' . PHP_EOL;
echo $r->getRandStrByShuffle(6, array('custom'=>'13579', 'custom_type'=>'override')) . ' - getRandStrByShuffle ' . PHP_EOL;

echo PHP_EOL . '生成6位偶数' . PHP_EOL;
echo $r->getRandStrByStrShuffle(6, array('custom'=>'02468', 'custom_type'=>'override')) . ' - getRandStrByStrShuffle ' . PHP_EOL;
echo $r->getRandStrByShuffle(6, array('custom'=>'02468', 'custom_type'=>'override')) . ' - getRandStrByShuffle ' . PHP_EOL;

echo PHP_EOL . '生成6位随机数，其中首位不为0' . PHP_EOL;
echo $r->getRandNumber(10, false);
