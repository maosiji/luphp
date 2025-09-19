<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : code@maosiji.cn
 * date                 : 2024-09-30 12:26
 * update               :
 * project              : luphp
 * description          :
 */

namespace MAOSIJI\LU;
//session_start();

if ( !class_exists('LUSession') ) {
    class LUSession {

        private static $instance = null; // 静态实例
        private $prefix = 'maosiji_lu_';
        private $started = false;
        private function __construct() {
            $this->start();
        }
        private function __clone()
        {

        }

        public static function getInstance(): self {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * 安全启动 Session（在 init 钩子后调用）
         *
         * @return void
         */
        private function start() {

            if ( !$this->started ) {

                // ✅ 禁止通过 URL 传递 Session ID，防止嗅探、劫持
                ini_set('session.use_only_cookies', 1);

                // ✅ 防止 JavaScript 通过 document.cookie获取 Session ID，防 XSS 攻击窃取
                ini_set('session.cookie_httponly', 1);

                // ✅ 仅通过 HTTPS 传输 Cookie，防止中间人攻击（如 HTTP 明文传输）
                if ( (new LUUrl())->is_https() ) {
                    ini_set('session.cookie_secure', 1); // 仅在 HTTPS 下启用
                }

                // 实现跨子域共享 Session
    //            ini_set('session.cookie_domain', '.example.com');

                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                    // ✅ 安全增强：启动后重新生成 Session ID，防止 Session Fixation
                    session_regenerate_id(true); // 只在首次启动时再生 ID
                    $this->started = true;
                }
            }
        }

        /**
         * 设置
         * @param string $key
         * @param string $value
         * @param int $expire   :存在时长，单位 秒
         * @param int $current_timestamp :当前时间戳，若不传入，则默认使用 time()
         * @return
         */
        public function set(string $key, string $value, int $expire=0, int $current_timestamp=0) {

            $current_timestamp = $current_timestamp>0 ? $current_timestamp : time();

            return $_SESSION[$this->prefix . $key] = [
                'value' => json_encode($value),
                'expire' => $expire > 0 ? $current_timestamp + $expire : 0,
                'set_at' => $current_timestamp
            ];
        }

        public function get(string $key, int $current_timestamp=0) {

            $full_key = $this->prefix . $key;
            if (!isset($_SESSION[$full_key])) return null;

            $data = $_SESSION[$full_key];
            $current_timestamp = $current_timestamp>0 ? $current_timestamp : time();

            // 检查是否过期
            if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                unset($_SESSION[$full_key]);
                return null;
            }

            return json_decode($data['value'], true);
        }

        public function getMeta(string $key, int $current_timestamp=0) {

            $full_key = $this->prefix . $key;
            if (!isset($_SESSION[$full_key])) return null;

            $data = $_SESSION[$full_key];
            $current_timestamp = $current_timestamp>0 ? $current_timestamp : time();

            // 检查是否过期
            if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                unset($_SESSION[$full_key]);
                return null;
            }

            return [
                'value' => json_decode($data['value'], true),
                'expire' => $data['expire'],
                'set_at' => $data['set_at'],
                'age' => $current_timestamp - $data['set_at'], // 已存在多少秒
            ];
        }

        public function check(string $key, string $value): bool {
            return $this->get($key) === $value;
        }

        public function delete(string $key): bool {

            $full_key = $this->prefix . $key;
            if (isset($_SESSION[$full_key])) {
                unset($_SESSION[$full_key]);
                return true;
            }

            return false;
        }

        /**
         * 清理所有过期 Session 项（可选定时调用）
         */
        public function gc( int $current_timestamp=0 ) {

            if ( mt_rand(1, 100)>20 ) return;

            $current_timestamp = $current_timestamp>0 ? $current_timestamp : time();

            foreach ($_SESSION as $key => $data) {
                if (strpos($key, $this->prefix) === 0 && is_array($data) && isset($data['expire'])) {
                    if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                        unset($_SESSION[$key]);
                    }
                }
            }
        }

        /**
         * 销毁整个 Session（用于用户退出登录）
         */
        public function destroy(): bool {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = []; // 清空内存数据
                session_destroy(); // 删除服务器端 Session 文件
                return true;
            }
            return false;
        }


    }
}

// 在插件主文件或 functions.php
//add_action('init', function() {
//    $session = \MAOSIJI\LU\LUSession::getInstance();
//    $wp_now = current_time('timestamp');
//    add_action('shutdown', function() use ($session, $wp_now) {
//        $session->gc($wp_now);
//    });
//});