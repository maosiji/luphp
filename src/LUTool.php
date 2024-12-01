<?php

namespace MAOSIJI\luphp;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : phpsuda
 * official website     : maosiji.com
 * official name        : PHP速搭
 * description          : 这家伙很懒，没有写介绍
 * read me              :
 * remind               ：
 */
if ( !class_exists( 'LUTool' ) ) {
	class LUTool
	{
		
		function __construct ()
		{
		}
		
		/**
		 * @param $length    int 随机数位数
		 *
		 * @return int		指定位数的随机数
		 */
		public function getRandNumber (int $length = 6 ): int
		{
			return rand( pow( 10, $length - 1 ), pow( 10, $length ) - 1 );
		}
		
		
	}
}
