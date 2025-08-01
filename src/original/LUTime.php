<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-20 17:50
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU;
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
		public function calculate_timediff( int $begin_time, int $end_time ): array
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

        /** 计算年龄
         * @param string $birthday
         * @return int
         */
        public function age( string $birthday )
        {
            // 创建出生日期对象
            $birthDateTime = \DateTime::createFromFormat('Y-m-d', $birthday);

            // 创建当前日期对象
            $currentDateTime = new \DateTime();

            // 计算日期差
            return $currentDateTime->diff($birthDateTime)->y;
        }


        /****************************************************
         * 获取未来最近的整秒时间（戳）
         *****************************************************/
        /**
         * 获取 DateTime 对象，表示未来最近的整秒时间点
         *
         * @param int $timestamp 可选 Unix 时间戳
         * @return \DateTime
         */
        private function _get_next_full_second_time(int $timestamp = 0): \DateTime
        {
            if ($timestamp > 0) {
                $now = new \DateTime("@$timestamp");
            } else {
                $now = new \DateTime();
            }

            $seconds = (int)$now->format('s');

            if ($seconds === 59) {
                $now->modify('+1 minute');
                $now->setTime((int)$now->format('H'), (int)$now->format('i'), 0);
            } else {
                $now->setTime((int)$now->format('H'), (int)$now->format('i'), 0);
                $now->modify('+1 minute');
            }

            return $now;
        }
        /**
         * 获取未来最近的整秒时间（格式化字符串）
         *
         * @param int $timestamp 可选 Unix 时间戳
         * @return string
         */
        public function get_next_full_second_time(int $timestamp = 0): string
        {
            $datetime = $this->_get_next_full_second_time($timestamp);
            return $datetime->format('Y-m-d H:i:s');
        }
        /**
         * 获取未来最近的整秒时间戳
         *
         * @param int $timestamp 可选 Unix 时间戳
         * @return int
         */
        public function get_next_full_second_timestamp(int $timestamp = 0): int
        {
            $datetime = $this->_get_next_full_second_time($timestamp);
            return $datetime->getTimestamp();
        }




	}
}
