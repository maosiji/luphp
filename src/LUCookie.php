<?php
namespace MAOSIJI\LUPHP;
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-11-26 22:26
 * update               : 
 * project              : luphp
 */
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
            setcookie($key, $value, $timediff, '/');
        }

        /**
         * @param string $key	        : 键
         *
         * @return string                : 若该key存在，则返回其值
         */
        public function get( string $key ): string
        {
            if ( isset( $_COOKIE[$key] ) ) {
                return $_COOKIE[$key];
            }

            return '';
        }

        /**
         * @param string $key	        : 键
         * @param string $value	        : 值
         *
         * @return bool                 :
         */
        public function check( string $key, string $value ): bool
        {
            if ( isset( $_COOKIE[$key] ) && $_COOKIE[$key] === $value ) {
                return true;
            }

            return false;
        }

        /**
         * @param string $key	        : 键
         */
        public function delete( string $key )
        {
            if ( isset( $_COOKIE[$key] ) ) {
                setcookie($key, '', time() - 3600, '/');
            }
        }

    }
}