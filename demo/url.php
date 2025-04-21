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
use MAOSIJI\LUPHP\LUUrl;

$luurl = new LUUrl();

// 获取当前 URL
$currentUrl = $luurl->get();

var_dump($currentUrl);
echo '<br>';

// 给url添加参数
$addParam = array(
	'name'=>'maosiji',
	'age'=>'80',
);
$urlAddParam = $luurl->update_params($addParam, $currentUrl);

var_dump($urlAddParam); // ?name=maosiji&age=80
echo '<br>';

// 给url删除参数
$deleteParam = array(
	'age'=>'80',
);
$urlDeleteParam = $luurl->delete_params($deleteParam, $urlAddParam);

var_dump($urlDeleteParam); // ?name=maosiji
echo '<br>';