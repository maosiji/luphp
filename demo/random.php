<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-07 20:03
 * update               : 
 * project              : luphp
 */

require __DIR__ . '/../vendor/autoload.php';

use MAOSIJI\LUPHP\original\LURandom;

$r = new LURandom();

echo '生成6位随机数，其中首位不为0' . '<br>';
echo $r->rand_number(6, false) . '<br>';
echo '生成6位随机数，其中首位可以为0' . '<br>';
echo $r->rand_number() . '<br>';

echo '生成6位随机数，其中首位不为0，的奇数' . '<br>';
echo $r->rand_number(5, false).$r->rand_odd() . '<br>';
echo '生成6位随机数，其中首位不为0，的偶数' . '<br>';
echo $r->rand_number(5, false).$r->rand_even() . '<br>';
