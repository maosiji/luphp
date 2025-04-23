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
require __DIR__ . '/../vendor/autoload.php';

use MAOSIJI\LUPHP\original\LUNo19;

$vd = new LUNo19();

$n1 = $vd->create();
$n2 = $vd->create('41120241202', 11);
$n3 = $vd->create('2024120220101', 13);
$n4 = $vd->create('', 1, 0);
$n5 = $vd->create('', 1, 1);

echo '生成19位可验证数字字符串' . '<br>';
echo $n1 . ' ['.strlen($n1).']' . '<br>';
echo $n2 . ' ['.strlen($n2).']' . '<br>';
echo $n3 . ' ['.strlen($n3).']' . '<br>';
echo '<br>' . '生成19位可验证数字字符串，其中倒数第二位为男女识别' . '<br>';
echo $n4 . ' ['.strlen($n4).']' . '<br>';
echo $n5 . ' ['.strlen($n5).']' . '<br>';

echo '<br>' . '验证字符串' . '<br>';
echo '2146218267533407750 - ' . ($vd->verify('2146218267533407750') ? '验证通过' : '验证不通过') . '<br>';
echo '4112024120205709182 - ' . ($vd->verify('4112024120205709182', 11) ? '验证通过' : '验证不通过') . '<br>';
echo '2024120220101057399 - ' . ($vd->verify('2024120220101057399', 13) ? '验证通过' : '验证不通过') . '<br>';
echo '2024120220101057392 - ' . ($vd->verify('2024120220101057392', 13) ? '验证通过' : '验证不通过') . '<br>';


