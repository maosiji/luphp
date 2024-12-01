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
if ( !class_exists('LUSession') ) {
    class LUSession {

        function __construct() {}

        /**
         * @param string $key	        : 键
         * @param string $value	        : 值
         */
        public function setKeyValue( string $key, string $value )
        {
            $_SESSION[$key] = $value;
        }

        /**
         * @param string $key	        : 键
         *
         * @return string               : 若存在该key，则返回其值
         */
        public function getKeyValue( string $key ): string
        {
            if ( isset($_SESSION[$key]) ) {
                return $_SESSION[$key];
            }

            return '';
        }

        /**
         * @param string $key : 键
         * @param string $value : 值
         *
         * @return bool : 验证设置的session
         */
        public function checkKeyValue( string $key, string $value ): bool
        {
            if ( isset($_SESSION[$key]) && $_SESSION[$key] === $value ) {
                return true;
            }

            return false;
        }

        /**
         * @param string $key	        : 键
         */
        public function deleteKeyValue( string $key )
        {
            if ( isset($_SESSION[$key]) ) {
                unset($_SESSION[$key]);
            }
        }


    }
}