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
        private $default_expire;

        /**
         * 构造函数：允许自定义 Cookie 名和默认过期时间
         *
         * @param string $cookie_name Cookie 名称（必须唯一，避免冲突）
         * @param int    $default_expire 默认过期时间（秒）
         */
        public function __construct( string $cookie_name, int $default_expire = 300 ) {
            $this->cookie_name = $cookie_name;
            $this->default_expire = $default_expire;
        }

        /**
         * 获取当前用户的 state（幂等：有效期内重复调用返回相同值）
         *
         * @param string $new_state_prefix 前缀
         * @param int $state_length 不包含前缀的长度
         * @param int|null $expire_seconds 有效期（秒），null 表示使用默认值
         * @return string state 字符串
         */
        public function get_state( string $new_state_prefix, int $state_length=10, int $expire_seconds = null ) {

            $expire = $expire_seconds !== null ? $expire_seconds : $this->default_expire;
            $existing = $this->get_existing_state();

            if ($existing !== false) {
                return $existing;
            }

            // 使用新 state
            $new_state = $new_state_prefix.$this->generate_secure_state($state_length);
            $data = json_encode(array($new_state, time()));

            // 加密并写入 Cookie
            $encrypted = base64_encode(wp_encrypt($data, AUTH_KEY));
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

            return $new_state;
        }

        /**
         * 验证一次（不管是否成功）都会消除 state（用于 OAuth 回调）
         *
         * @param string $input_state 来自回调 URL 的 state
         * @return bool 是否验证通过
         */
        public function verify_and_clear_state( string $input_state ) {

            $existing = $this->get_existing_state();
            if ($existing === false) {
                return false;
            }

            $valid = hash_equals($existing, $input_state);
            $this->clear();
            return $valid;
        }

        /**
         * 从 Cookie 中读取、解密、验证是否过期
         *
         * @return string|false 明文 state 或 false
         */
        private function get_existing_state() {

            if (!isset($_COOKIE[$this->cookie_name])) {
                return false;
            }

            $encrypted = $_COOKIE[$this->cookie_name];
            $raw = base64_decode($encrypted, true);
            if ($raw === false) {
                $this->clear();
                return false;
            }

            $data = wp_decrypt($raw, AUTH_KEY);
            if (!$data) {
                $this->clear();
                return false;
            }

            $parsed = json_decode($data, true);
            if (!is_array($parsed) || count($parsed) !== 2) {
                $this->clear();
                return false;
            }

            list($state, $timestamp) = $parsed;
            if (!$state || !is_numeric($timestamp)) {
                $this->clear();
                return false;
            }

            if (time() - (int)$timestamp > $this->default_expire) {
                $this->clear();
                return false;
            }

            return $state;
        }

        /**
         * 清除当前配置的 Cookie
         */
        private function clear() {
            setcookie($this->cookie_name, '', time() - 3600, '/');
            unset($_COOKIE[$this->cookie_name]);
        }

        /**
         * 生成仅含 [a-zA-Z0-9] 的高熵唯一 state
         *
         * 随机性质量：完全均匀
         * 性能：接近最优
         * 安全标准：符合最高标准
         *
         * @param int $length 生成长度（不含前缀）
         * @return string 格式：state_xxx...
         */
        private function generate_secure_state($length = 32) {
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $state = '';
            $bytes = random_bytes($length + 10); // 多取一点，避免重试
            $i = 0;
            $maxByte = 248; // floor(256 / 62) * 62 = 4 * 62 = 248

            while (strlen($state) < $length) {
                if ($i >= strlen($bytes)) {
                    // 极端情况：补充新随机字节
                    $bytes = random_bytes($length);
                    $i = 0;
                }

                $byte = ord($bytes[$i]);
                $i++;

                if ($byte < $maxByte) {
                    $state .= $pool[$byte % 62];
                }
                // 否则丢弃该字节（拒绝采样），继续下一轮
            }

            return $state;
        }




    }
}