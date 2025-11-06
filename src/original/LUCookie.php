<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-11-26 22:26
 * update               : 
 * project              : luphp
 * description          : 安全封装 Cookie，支持过期、前缀、自动清理、JSON 存储
 *
 * 特点：
 *   - 使用 JSON 编码，避免 unserialize() 安全风险
 *   - 支持传入当前时间戳，统一 WordPress 时区逻辑
 *   - 自动设置 Secure、HttpOnly、SameSite=Lax
 *   - 支持跨子域共享（自动计算 domain 或手动指定）
 *   - 自动清理过期 Cookie（gc）
 */

namespace MAOSIJI\LU;
if ( !class_exists('LUCookie') ) {
    class LUCookie {

        private static $instance = null;
        private $prefix = 'maosiji_lu_';
        private $default_expire = 3600;
        private $path = '/';
        // Cookie 作用域（自动计算或手动指定，支持跨子域）
        private $domain = '';
        private $secure = false;
        private $httponly = true;
        // SameSite 策略，防 CSRF（可选：Strict / Lax / None）
        private $samesite = 'Lax';
        private function __construct($custom_domain=null) {
            $this->secure = (new LUUrl())->is_https();
            $this->domain = $this->getCurrentDomain($custom_domain);
        }
        private function __clone() {}

        public static function getInstance($custom_domain = null): self {
            if (self::$instance === null) {
                self::$instance = new self($custom_domain);
            }
            return self::$instance;
        }

        /**
         * 自动计算主域（用于跨子域共享 Cookie）
         * 修正版：三级以上域名，统一退回到最后两段（如 .example.com）
         * 注意：不适用于 .co.uk 等复杂后缀（如需支持，请使用 TLDExtract 库或手动传入 $custom_domain）
         *
         * @param string|null $custom_domain 手动指定的主域（如 '.example.com' 或 '.mysite.co.uk'），优先级最高
         * @return string 计算后的 domain 字符串
         */
        private function getCurrentDomain($custom_domain = null) {
            // 如果手动指定了主域，直接返回（开发者最清楚自己的域名结构）
            if ($custom_domain !== null && is_string($custom_domain) && $custom_domain !== '') {
                return $custom_domain;
            }

            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            if (strpos($host, ':') !== false) {
                $host = substr($host, 0, strpos($host, ':'));
            }

            $parts = explode('.', $host);

            // 如果是 IP 地址或本地域名，不加点
            if (filter_var($host, FILTER_VALIDATE_IP) || in_array($host, array('localhost', '127.0.0.1'))) {
                return $host;
            }

            // 如果是标准二级域名（如 example.com），直接返回
            if (count($parts) === 2) {
                return $host;
            }

            // 如果是三级或以上（如 shop.blog.example.com），退回到最后两段
            if (count($parts) > 2) {
                return '.' . implode('.', array_slice($parts, -2));
            }

            return $host;
        }

        /**
         * 兼容 PHP 7.0 的 setcookie（支持 SameSite）
         *
         * @param string $name
         * @param string $value
         * @param int $expires
         * @param string $path
         * @param string $domain
         * @param bool $secure
         * @param bool $httponly
         * @param string $samesite SameSite 值（'Lax', 'Strict', 'None'）
         * @return bool
         */
        private function setCookieCompat($name, $value, $expires, $path, $domain, $secure, $httponly, $samesite = 'Lax') {
            // 构造 Cookie 字符串
            $cookie = sprintf(
                '%s=%s; expires=%s; path=%s%s%s%s%s',
                rawurlencode($name),
                rawurlencode($value),
                gmdate('D, d-M-Y H:i:s T', $expires > 0 ? $expires : 2147483647), // 会话 Cookie 用最大时间
                $path,
                $domain ? '; domain=' . $domain : '',
                $secure ? '; secure' : '',
                $httponly ? '; HttpOnly' : '',
                $samesite ? '; SameSite=' . $samesite : ''
            );

            // 发送头
            if (headers_sent()) {
                return false;
            }

            header('Set-Cookie: ' . $cookie, false);
            return true;
        }

        /**
         * 设置一个 Cookie（自动 JSON 编码 + 过期时间）
         *
         * @param string $key 键名（自动加前缀）
         * @param mixed $value 值（支持数组、对象、字符串等，自动 json_encode）
         * @param int $expire 生命周期（秒），0 = 会话 Cookie（浏览器关闭即失效）
         * @param int $current_timestamp 当前时间戳（用于统一时区，如 WordPress 的 current_time('timestamp')）
         * @return bool 是否设置成功
         */
        public function set($key, $value, $expire = 0, $current_timestamp = 0) {
            // 如果未传入当前时间，使用服务器时间
            $current_timestamp = $current_timestamp > 0 ? $current_timestamp : time();

            // 尝试将值 JSON 编码（安全，无反序列化漏洞）
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                // 编码失败，记录日志并返回 false
                error_log("LUCookie::set() failed to encode value for key '$key': " . json_last_error_msg());
                return false;
            }

            // 完整键名 = 前缀 + 用户键名
            $full_key = $this->prefix . $key;

            // 计算过期时间戳（0 = 会话 Cookie）
            $expire_time = $expire > 0 ? $current_timestamp + $expire : 0;

            // 构造存储结构：包含值、过期时间、设置时间
            $cookie_data = json_encode(array(
                'value' => $encoded,     // 用户数据（已编码）
                'expire' => $expire_time, // 过期时间戳
                'set_at' => $current_timestamp // 设置时间戳（用于计算 age）
            ));

            if ($cookie_data === false) {
                error_log("LUCookie::set() failed to encode cookie structure for key '$key': " . json_last_error_msg());
                return false;
            }

            // 使用兼容函数设置 Cookie（支持 PHP 7.0 + SameSite）
            return $this->setCookieCompat(
                $full_key,
                $cookie_data,
                $expire_time,
                $this->path,
                $this->domain,
                $this->secure,
                $this->httponly,
                $this->samesite
            );
        }

        /**
         * 获取 Cookie 值（自动 JSON 解码 + 自动清理过期项）
         *
         * @param string $key 用户键名（不带前缀）
         * @param int $current_timestamp 当前时间戳（用于检查是否过期）
         * @return mixed|null 解码后的值，或 null（不存在/过期/损坏）
         */
        public function get($key, $current_timestamp = 0) {
            $full_key = $this->prefix . $key;

            // 如果 Cookie 不存在，直接返回 null
            if (!isset($_COOKIE[$full_key])) return false;

            // 如果未传入当前时间，使用服务器时间
            $current_timestamp = $current_timestamp > 0 ? $current_timestamp : time();

            try {
                // 解析 Cookie 中的 JSON 结构
                $data = json_decode($_COOKIE[$full_key], true);

                // 检查结构是否合法
                if (!is_array($data) || !isset($data['value'], $data['expire'])) {
                    throw new \Exception('Invalid cookie structure');
                }

                // 检查是否过期
                if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                    $this->delete($key); // 自动清理过期 Cookie
                    return false;
                }

                // 解码用户数据并返回
                $decoded_value = json_decode($data['value'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Failed to decode user value: ' . json_last_error_msg());
                }

                return $decoded_value;

            } catch (\Exception $e) {
                // 数据损坏，清理并记录日志
                $this->delete($key);
                error_log("LUCookie::get() failed to decode cookie '$key': " . $e->getMessage());
                return false;
            }
        }

        /**
         * 检查 Cookie 值是否等于指定值（和 LUSession 接口一致）
         *
         * @param string $key
         * @param mixed $value
         * @return bool
         */
        public function check($key, $value) {
            return $this->get($key) === $value;
        }

        /**
         * 获取 Cookie 元数据（含 age、expire、set_at 等）
         *
         * @param string $key
         * @param int $current_timestamp
         * @return array|null
         */
        public function getMeta($key, $current_timestamp = 0) {
            $full_key = $this->prefix . $key;
            if (!isset($_COOKIE[$full_key])) return false;

            $current_timestamp = $current_timestamp > 0 ? $current_timestamp : time();

            try {
                $data = json_decode($_COOKIE[$full_key], true);

                if (!is_array($data) || !isset($data['value'], $data['expire'], $data['set_at'])) {
                    throw new \Exception('Invalid cookie structure');
                }

                // 检查过期
                if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                    $this->delete($key);
                    return false;
                }

                // 解码用户值
                $value = json_decode($data['value'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('Failed to decode user value: ' . json_last_error_msg());
                }

                // 返回元数据
                return array(
                    'value' => $value,                   // 用户数据
                    'expire' => $data['expire'],         // 过期时间戳
                    'set_at' => $data['set_at'],         // 设置时间戳
                    'age' => $current_timestamp - $data['set_at'], // 已存活秒数
                );

            } catch (\Exception $e) {
                $this->delete($key);
                error_log("LUCookie::getMeta() failed for '$key': " . $e->getMessage());
                return false;
            }
        }

        /**
         * 删除指定 Cookie（设置过期时间为过去）
         *
         * @param string $key 用户键名（不带前缀）
         * @return bool 是否删除成功
         */
        public function delete($key) {
            $full_key = $this->prefix . $key;

            // 使用兼容函数删除
            return $this->setCookieCompat(
                $full_key,
                '',
                time() - 3600,          // 过去的时间
                $this->path,
                $this->domain,
                $this->secure,
                $this->httponly,
                $this->samesite
            );
        }

        /**
         * 销毁所有当前前缀的 Cookie（用于用户退出登录）
         *
         * @return bool 是否有 Cookie 被删除
         */
        public function destroy() {
            $deleted = false;
            foreach ($_COOKIE as $key => $value) {
                // 检查是否是本前缀的 Cookie
                if (strpos($key, $this->prefix) === 0) {
                    // 提取原始键名（去掉前缀）
                    $original_key = substr($key, strlen($this->prefix));
                    $this->delete($original_key);
                    $deleted = true;
                }
            }
            return $deleted;
        }

        /**
         * 清理所有过期 Cookie（20% 概率执行，性能优化）
         * 可在 shutdown 钩子中自动调用
         *
         * @param int $current_timestamp 当前时间戳（用于统一时区）
         */
        public function gc($current_timestamp = 0) {
            // 20% 概率执行，避免每次请求都扫描（性能优化）
            if (mt_rand(1, 100) > 20) return;

            $current_timestamp = $current_timestamp > 0 ? $current_timestamp : time();

            foreach ($_COOKIE as $key => $value) {
                // 只处理本前缀的 Cookie
                if (strpos($key, $this->prefix) === 0) {
                    try {
                        $data = json_decode($value, true);
                        // 检查结构和过期时间
                        if (is_array($data) && isset($data['expire']) && $data['expire'] > 0 && $current_timestamp > $data['expire']) {
                            // 提取原始键名并删除
                            $original_key = substr($key, strlen($this->prefix));
                            $this->delete($original_key);
                        }
                    } catch (\Exception $e) {
                        // 数据损坏，也删除
                        $original_key = substr($key, strlen($this->prefix));
                        $this->delete($original_key);
                    }
                }
            }
        }




    }
}