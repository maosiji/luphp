<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2025-11-09 03:57
 * update               : 
 * project              : luphp
 */

namespace MAOSIJI\LU\WP;
use MAOSIJI\LU\LUEncryptor;
use MAOSIJI\LU\LURandom;

if ( ! defined( 'ABSPATH' ) ) { die; }
if (!class_exists('LUWPCookie')) {

    /**
     * 可配置的 OAuth state 管理器（兼容 PHP 7.0+）
     *
     * 使用方式：
     *   $wechat = new XYYS_OAuth_State('xyys_wechat_state', 600);
     *   $state = $wechat->get_state();
     */
    class LUWPCookie {

        private $cookie_name;
        private $default_expire_seconds;
        private $default_encrypt_key;

        /**
         * 构造函数：允许自定义 Cookie 名和默认过期时间
         *
         * @param string $cookie_name Cookie 名称（必须唯一，避免冲突）
         * @param int    $default_expire 默认过期时间（秒）
         */
        public function __construct( string $cookie_name, int $default_expire_seconds = 300, string $default_encrypt_key = 'kdkdieror596kfgkg96kf' ) {
            $this->cookie_name = $cookie_name;
            $this->default_expire_seconds = $default_expire_seconds;
            $this->default_encrypt_key = $default_encrypt_key;
        }

        /**
         * 获取当前用户的 唯一值（幂等：有效期内重复调用返回相同值）加密保存的
         *
         * @param string $value_prefix 前缀
         * @param int $state_length 值长度（不包含前缀的）
         * @param string $encrypt_key 加密的 key
         * @param int|null $expire_seconds 有效期（秒），null 表示使用默认值
         * @return string 字符串
         */
        public function get_or_create_encrypted_value_and_set( string $value_prefix, int $value_length=10, string $encrypt_key=null, int $expire_seconds = null ) {

            $expire = $expire_seconds !== null ? $expire_seconds : $this->default_expire_seconds;
            $e_key = $encrypt_key !== null ? $encrypt_key : $this->default_encrypt_key;
            $existing = $this->_get_existing_encrypted_value( $e_key );

            if ($existing !== false) {
                return $existing;
            }

            // 使用新 state
            $new_value = $value_prefix.($value_length>0 ? (new LURandom())->generate_secure_str($value_length) : '');
            $data = json_encode(array($new_value, time()));

            // 加密并写入 Cookie
            $encrypted = (new LUEncryptor($e_key))->encrypt($data);
            setcookie(
                $this->cookie_name,
                $encrypted,
                time() + $expire,
                '/',
                '',
                is_ssl(),
                true // HttpOnly
            );

            // 更新本次请求的 $_COOKIE
            $_COOKIE[$this->cookie_name] = $encrypted;

            return $new_value;
        }

        /**
         * 验证一次（不管是否成功）都会消除 唯一值（用于 OAuth 回调）
         *
         * @param string $input_state 来自回调 URL 的 state
         * @return bool 是否验证通过
         */
        public function verify_and_destroy_encrypted_value( string $input_state, string $encrypt_key=null ) {

            $e_key = $encrypt_key !== null ? $encrypt_key : $this->default_encrypt_key;

            $existing = $this->_get_existing_encrypted_value( $e_key );
            if ($existing === false) {
                return false;
            }

            $valid = hash_equals($existing, $input_state);
            $this->_destroy_encrypted_value();
            return $valid;
        }

        /**
         * 从 Cookie 中读取、解密、验证是否过期
         *
         * @return string|false 明文 state 或 false
         */
        private function _get_existing_encrypted_value( string $encrypt_key=null ) {

            if (!isset($_COOKIE[$this->cookie_name])) {
                return false;
            }

            $encrypted = $_COOKIE[$this->cookie_name];
            $data = (new LUEncryptor($encrypt_key))->decrypt($encrypted);
            if (!$data) {
                $this->_destroy_encrypted_value();
                return false;
            }

            $parsed = json_decode($data, true);
            if (!is_array($parsed) || count($parsed) !== 2) {
                $this->_destroy_encrypted_value();
                return false;
            }

            list($state, $timestamp) = $parsed;
            if (!$state || !is_numeric($timestamp)) {
                $this->_destroy_encrypted_value();
                return false;
            }

            if (time() - (int)$timestamp > $this->default_expire_seconds) {
                $this->_destroy_encrypted_value();
                return false;
            }

            return $state;
        }

        /**
         * 清除当前配置的 Cookie
         */
        private function _destroy_encrypted_value() {
            setcookie($this->cookie_name, '', time() - 3600, '/');
            unset($_COOKIE[$this->cookie_name]);
        }






    }
}