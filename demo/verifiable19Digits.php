<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-12-06 21:40
 * update               : 
 * description          :
 */
require __DIR__ . '/../src/LURandom.php';
require __DIR__ . '/../src/LUNO19.php';

use MAOSIJI\LUPHP\LURandom;
use MAOSIJI\LUPHP\LUNO19;

$vd = new LUNO19();

$n1 = $vd->create();
$n2 = $vd->create('41120241202', 11);
$n3 = $vd->create('2024120220101', 13);
$n4 = $vd->create('', 1, 0);
$n5 = $vd->create('', 1, 1);

echo PHP_EOL . '生成19位可验证数字字符串' . PHP_EOL;
echo $n1 . ' ['.strlen($n1).']' . PHP_EOL;
echo $n2 . ' ['.strlen($n2).']' . PHP_EOL;
echo $n3 . ' ['.strlen($n3).']' . PHP_EOL;
echo PHP_EOL . '生成19位可验证数字字符串，其中倒数第二位为男女识别' . PHP_EOL;
echo $n4 . ' ['.strlen($n4).']' . PHP_EOL;
echo $n5 . ' ['.strlen($n5).']' . PHP_EOL;

echo PHP_EOL . '验证字符串' . PHP_EOL;
echo '2146218267533407750 - ' . ($vd->verifyNumber('2146218267533407750') ? '验证通过' : '验证不通过') . PHP_EOL;
echo '4112024120205709182 - ' . ($vd->verifyNumber('4112024120205709182', 11) ? '验证通过' : '验证不通过') . PHP_EOL;
echo '2024120220101057399 - ' . ($vd->verifyNumber('2024120220101057399', 13) ? '验证通过' : '验证不通过') . PHP_EOL;
echo '2024120220101057392 - ' . ($vd->verifyNumber('2024120220101057392', 13) ? '验证通过' : '验证不通过') . PHP_EOL;


