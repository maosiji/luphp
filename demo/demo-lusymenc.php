<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-07-31 21:40
 * update               : 
 * project              : luphp
 */

require __DIR__ . '/../vendor/autoload.php';

use MAOSIJI\LU\LUSymEnc;

$luse = new LUSymEnc('dsk34');

$value = '我是要加密的文本';
echo '原始文本：'.$value.'<br>';

$jia = $luse->encrypt( $value );
echo '加密后：'.$jia.'<br>';

$jie = $luse->decrypt( $jia );
echo '解密后：'.$jie;
