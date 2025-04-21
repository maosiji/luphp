<?php

namespace MAOSIJI\LUPHP\price;
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
if ( !class_exists('MaoPrice') ) {
	class MaoPrice {
		
		function __construct (  )
		{
			
		}
		
		/*
		 * 价格转换器  最终效果：10.00
		 *
		 * 应用：
		 * 		玄鸟支付
		 * */
		public function format ( $price ): string
		{

//		if ( !is_numeric($price) ) { $price = 0.00; }
			
			if ( doubleval( $price ) <= 0.00 ) {
				$price = 0.00;
			}
			// 判断是否为小数
			if ( strpos( $price, '.' ) ) {
				$price = ceil( $price * 100 ) / 100;
			}
			
			return sprintf( "%.2f", $price );
		}
		
		
		
	}
}
