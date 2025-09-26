<?php
/*
 *
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-09-21 18:27
 * update               :
 * project              : luphp
 */

namespace MAOSIJI\LU\WP;
if ( ! defined( 'ABSPATH' ) ) { die; }
if (!class_exists('LUWPNonce')) {
    class LUWPNonce
    {

        function __construct()
        {
        }

        private function __clone()
        {
        }


        private function fe_wp_create_nonce()
        {
            if (!function_exists('wp_create_nonce')) {
                require_once(ABSPATH . 'wp-includes/pluggable.php');
            }
        }

        /**
         * @param string $str
         *
         * @return string 生成带有 uid 的 nonce
         */
        public function create_nonce(string $str): string
        {
            if (empty($str)) {
                return false;
            }
            $this->fe_wp_create_nonce();
            return wp_create_nonce('luphp-' . (empty($str) ? 'potkg95486' : $str) . '-fjrfj59696kg45-' . get_current_user_id());
        }

        /**
         * @param string $nonce
         * @param string $str
         *
         * @return false|true 验证带有 uid 的 nonce
         */
        public function verify_nonce(string $nonce, string $str): bool
        {
            if (empty($nonce) || empty($str)) {
                return false;
            }
            $this->fe_wp_create_nonce();
            return wp_verify_nonce($nonce, 'luphp-' . (empty($str) ? 'potkg95486' : $str) . '-fjrfj59696kg45-' . get_current_user_id());
        }


    }
}
