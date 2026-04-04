<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2026-04-04 19:54
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU\WP;
use MAOSIJI\LU\LUSend;

if ( ! defined( 'ABSPATH' ) ) { die; }
if ( ! class_exists( 'LUWPSafe' ) ) {
    class LUWPSafe {
        function __construct()
        {

        }
        private function __clone()
        {
        }

        // 默认时间间隔（秒）
        const DEFAULT_TIME_INTERVAL = 3;

        /**
         * 检查是否连续点击 AJAX 按钮，并禁止
         *
         * @param int $timediff 时间间隔（秒），默认 3 秒
         */
        public function check_too_many_requests( int $timediff = self::DEFAULT_TIME_INTERVAL ): array
        {
            $user_id = get_current_user_id();
            $transient_key = "check_too_many_requests_{$user_id}";

            // 检查是否在冷却期内
            if (get_transient($transient_key)) {
                return (new LUSend())->send_array( 0, '请求太频繁，请稍后再试' );
            }

            // 设置冷却标记，有效期2秒
            set_transient($transient_key, 1, $timediff);

            return (new LUSend())->send_array( 1, '放行' );
        }



    }
}