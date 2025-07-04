<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-11-26 22:26
 * update               : 
 * project              : luphp
 */
namespace MAOSIJI\LU;
if ( !class_exists('LUCookie') ) {
    class LUCookie {

        function __construct() {}

        /**
         * @param string $key	        : 键
         * @param string $value	        : 值
         * @param int    $timediff		: 时间间隔，默认600秒，即10分钟。单位是 秒。用于cookie的设置。
         */
        public function set( string $key, string $value, int $timediff=600 )
        {
            if ($timediff < 0) {
                throw new \InvalidArgumentException("过期时间不能为负数");
            }

            setcookie($key, $value, time() + $timediff, '/');
        }

        /**
         * @param string $key	        : 键
         *
         * @return string|null          :  如果键不存在，返回 null
         */
        public function get( string $key )
        {
            return $_COOKIE[$key] ?? '';
        }

        /**
         * @param string $key	        : 键
         * @param string $value	        : 值
         *
         * @return bool                 :
         */
        public function check( string $key, string $value ): bool
        {
            return isset( $_COOKIE[$key] ) && $_COOKIE[$key] === $value;
        }

        /**
         * @param string $key	        : 键
         */
        public function delete( string $key )
        {
            if ( isset( $_COOKIE[$key] ) ) {
                setcookie($key, '', time() - 3600, '/');
                unset( $_COOKIE[$key] );
            }
        }

    }
}