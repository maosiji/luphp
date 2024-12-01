<?php
namespace MAOSIJI\luphp;
session_start();
date_default_timezone_set('Asia/Shanghai');
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-30 12:26
 * update               : 
 * project              : luphp
 */
if ( !class_exists('LUSafe') ) {
	class LUSafe {
		
		function __construct() {
		
		}
		
		/**
		 * @param int $timediff :自定义时间间隔，默认5秒
		 *
		 * @return void :判断是否连续点击 ajax 按钮，并禁止
		 */
		public function checkTooManyRequests( int $timediff=5 ) {
			// 检查 session 中是否有上一次请求的时间戳
			if (isset($_SESSION['last_request_time'])) {
				$lastRequestTime = $_SESSION['last_request_time'];
				$currentTime = time();
				
				// 计算两次请求之间的时间差
				$timeDifference = $currentTime - $lastRequestTime;
				
				// 设定最小请求间隔时间（秒）
				$minInterval = $timediff;
				
				if ($timeDifference < $minInterval) {
					header("HTTP/1.1 429 Too Many Requests");
					exit();
				}
			}
			
			// 更新session中的请求时间
			$_SESSION['last_request_time'] = time();
		}
		

		
	}
}
