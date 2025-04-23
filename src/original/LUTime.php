<?php
namespace MAOSIJI\LU;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */
if ( !class_exists( 'LUTime' ) ) {
	class LUTime
	{
		function __construct ()
		{
		
		}
		
		/**
		 * @param int $begin_time		: 时间戳 开始时间
		 * @param int $end_time        	: 时间戳 结束时间
		 *
		 * @return array        返回时间间隔数组 array('day'=>'', 'hour'=>'', 'min'=>'', 'sec=>'')
		 */
		public function calculate_timediff ( int $begin_time, int $end_time ): array
		{
			if ( $begin_time < $end_time ) {
				$starttime = $begin_time;
				$endtime = $end_time;
			} else {
				$starttime = $end_time;
				$endtime = $begin_time;
			}
			//计算天数
			$timediff = $endtime - $starttime;
			$days = intval( $timediff / 86400 );
			//计算小时数
			$remain = $timediff % 86400;
			$hours = intval( $remain / 3600 );
			//计算分钟数
			$remain = $remain % 3600;
			$mins = intval( $remain / 60 );
			//计算秒数
			$secs = $remain % 60;
			
			return array( "day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs );
		}
	}
}
