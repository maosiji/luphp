<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-30 12:26
 * update               : 2025-11-07 — 移除 $current_timestamp，仅用 time()，兼容 PHP 7.0+
 * project              : luphp
 * description          : 安全、带过期机制的 Session 封装类（PHP 7.0+ 兼容）
 */

namespace MAOSIJI\LU;

if (!class_exists('LUSession')) {
    class LUSession {

        private static $instance = null;
        private $prefix = 'maosiji_lu_';
        private $started = false;

        private function __construct() {
            $this->start();
        }

        private function __clone() {}

        /**
         * 获取单例实例
         *
         * @return LUSession
         */
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * 安全启动 Session
         */
        private function start() {
            if ($this->started || session_status() !== PHP_SESSION_NONE) {
                $this->started = true;
                return;
            }

            // 安全配置
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');

            if ((new LUUrl())->is_https()) {
                ini_set('session.cookie_secure', '1');
            }

            session_start();
            $this->started = true;
        }

        /**
         * 设置带过期时间的 Session 值
         *
         * @param string $key
         * @param string $value
         * @param int    $expire 秒数，0 表示永不过期
         * @return array
         */
        public function set($key, $value, $expire = 0) {
            $current_timestamp = time();
            return $_SESSION[$this->prefix . $key] = array(
                'value' => $value,
                'expire' => $expire > 0 ? $current_timestamp + $expire : 0,
                'set_at' => $current_timestamp
            );
        }

        /**
         * 获取值（自动清理过期项）
         *
         * @param string $key
         * @return mixed false on not found or expired, string value otherwise
         */
        public function get($key) {
            $full_key = $this->prefix . $key;
            if (!isset($_SESSION[$full_key])) {
                return false;
            }

            $data = $_SESSION[$full_key];
            $current_timestamp = time();

            if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                unset($_SESSION[$full_key]);
                return false;
            }

            return $data['value'];
        }

        /**
         * 获取元数据（含过期时间、设置时间等）
         *
         * @param string $key
         * @return array|false
         */
        public function getMeta($key) {
            $full_key = $this->prefix . $key;
            if (!isset($_SESSION[$full_key])) {
                return false;
            }

            $data = $_SESSION[$full_key];
            $current_timestamp = time();

            if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                unset($_SESSION[$full_key]);
                return false;
            }

            return array(
                'value'     => $data['value'],
                'expire'    => $data['expire'],
                'set_at'    => $data['set_at'],
                'age'       => $current_timestamp - $data['set_at'],
            );
        }

        /**
         * 检查键值是否匹配
         *
         * @param string $key
         * @param string $value
         * @return bool
         */
        public function check($key, $value) {
            return $this->get($key) === $value;
        }

        /**
         * 删除单个键
         *
         * @param string $key
         * @return bool
         */
        public function delete($key) {
            $full_key = $this->prefix . $key;
            if (isset($_SESSION[$full_key])) {
                unset($_SESSION[$full_key]);
                return true;
            }
            return false;
        }

        /**
         * 垃圾回收：清理当前会话中所有过期项
         * 20% 概率执行（性能优化）
         */
        public function gc() {
            if (mt_rand(1, 100) > 20) {
                return;
            }

            $current_timestamp = time();
            foreach ($_SESSION as $key => $data) {
                if (
                    is_string($key) &&
                    strpos($key, $this->prefix) === 0 &&
                    is_array($data) &&
                    isset($data['expire'])
                ) {
                    if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                        unset($_SESSION[$key]);
                    }
                }
            }
        }

        /**
         * 销毁整个会话（退出登录时调用）
         *
         * @return bool
         */
        public function destroy() {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = array();
                session_destroy();
                $this->started = false;
                return true;
            }
            return false;
        }

        /**
         * 获取当前 Session ID
         *
         * @return string
         */
        public function get_session_id() {
            return session_id();
        }
    }
}