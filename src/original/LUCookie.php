<?php
/*
 * author               : 猫斯基
 * url                  : maosiji.com
 * email                : 1394846666@qq.com
 * wechat               : maosiji-com
 * date                 : 2024-11-26 22:26
 * update               : 移除 $current_timestamp 参数，统一使用 time()；移除 json_encode/json_decode，改用自定义编码（无反序列化），兼容 PHP 7.0
 * project              : luphp
 * description          : 安全封装 Cookie，支持过期、前缀、自动清理、无 JSON 存储
 */

namespace MAOSIJI\LU;

if (!class_exists('LUCookie')) {
    class LUCookie {

        private static $instance = null;
        private $prefix = 'maosiji_lu_';
        private $path = '/';
        private $domain = '';
        private $secure = false;
        private $httponly = true;
        private $samesite = 'Lax';

        private function __construct($custom_domain = null) {
            $this->secure = (new LUUrl())->is_https();
            $this->domain = $this->getCurrentDomain($custom_domain);
        }

        private function __clone() {}

        public static function getInstance($custom_domain = null) {
            if (self::$instance === null) {
                self::$instance = new self($custom_domain);
            }
            return self::$instance;
        }

        private function getCurrentDomain($custom_domain = null) {
            if ($custom_domain !== null && is_string($custom_domain) && $custom_domain !== '') {
                return $custom_domain;
            }

            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            if (strpos($host, ':') !== false) {
                $host = substr($host, 0, strpos($host, ':'));
            }

            $parts = explode('.', $host);

            if (filter_var($host, FILTER_VALIDATE_IP) || in_array($host, array('localhost', '127.0.0.1'))) {
                return $host;
            }

            if (count($parts) === 2) {
                return $host;
            }

            if (count($parts) > 2) {
                return '.' . implode('.', array_slice($parts, -2));
            }

            return $host;
        }

        private function setCookieCompat($name, $value, $expire_time, $path, $domain, $secure, $httponly, $samesite = 'Lax') {
            $cookie = sprintf(
                '%s=%s; expires=%s; path=%s%s%s%s%s',
                rawurlencode($name),
                rawurlencode($value),
                gmdate('D, d-M-Y H:i:s T', $expire_time > 0 ? $expire_time : 2147483647),
                $path,
                $domain ? '; domain=' . $domain : '',
                $secure ? '; secure' : '',
                $httponly ? '; HttpOnly' : '',
                $samesite ? '; SameSite=' . $samesite : ''
            );

            if (headers_sent()) {
                return false;
            }

            header('Set-Cookie: ' . $cookie, false);
            return true;
        }

        /**
         * 自定义编码：将数组转为 base64 字符串（无 JSON）
         */
        private function encodeData(array $data) {
            // 构造格式：value=...&expire=...&set_at=...
            $parts = array();
            foreach ($data as $k => $v) {
                if ($k === 'value') {
                    $parts[] = $k . '=' . rawurlencode((string)$v);
                } else {
                    $parts[] = $k . '=' . (string)$v;
                }
            }
            $plain = implode('&', $parts);
            return base64_encode($plain);
        }

        /**
         * 自定义解码：还原为数组
         */
        private function decodeData($encoded) {
            $plain = base64_decode($encoded, true);
            if ($plain === false) {
                return null;
            }

            $parts = explode('&', $plain);
            $data = array();
            foreach ($parts as $part) {
                if (strpos($part, '=') === false) continue;
                list($key, $val) = explode('=', $part, 2);
                if ($key === 'value') {
                    $data[$key] = rawurldecode($val);
                } else {
                    // expire 和 set_at 必须是整数
                    if (!ctype_digit($val) && !($val[0] === '-' && ctype_digit(substr($val, 1)))) {
                        return null;
                    }
                    $data[$key] = (int)$val;
                }
            }

            if (!isset($data['value'], $data['expire'], $data['set_at'])) {
                return null;
            }

            return $data;
        }

        public function set($key, $value, $expire_seconds = 0) {
            $current_timestamp = time(); // ✅ 统一使用 time()
            $full_key = $this->prefix . $key;
            $expire_time = $expire_seconds > 0 ? $current_timestamp + $expire_seconds : 0;

            $cookie_data = array(
                'value' => (string)$value,
                'expire' => $expire_time,
                'set_at' => $current_timestamp
            );

            $encoded_value = $this->encodeData($cookie_data);

            return $this->setCookieCompat(
                $full_key,
                $encoded_value,
                $expire_time,
                $this->path,
                $this->domain,
                $this->secure,
                $this->httponly,
                $this->samesite
            );
        }

        public function get($key) {
            $full_key = $this->prefix . $key;
            if (!isset($_COOKIE[$full_key])) {
                return false;
            }

            $current_timestamp = time(); // ✅ 统一使用 time()

            try {
                $data = $this->decodeData($_COOKIE[$full_key]);
                if ($data === null) {
                    throw new \Exception('Invalid cookie structure');
                }

                if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                    $this->delete($key);
                    return false;
                }

                return $data['value'];

            } catch (\Exception $e) {
                $this->delete($key);
                error_log("LUCookie::get() failed to decode cookie '$key': " . $e->getMessage());
                return false;
            }
        }

        public function check($key, $value) {
            return $this->get($key) === $value;
        }

        public function getMeta($key) {
            $full_key = $this->prefix . $key;
            if (!isset($_COOKIE[$full_key])) {
                return false;
            }

            $current_timestamp = time(); // ✅ 统一使用 time()

            try {
                $data = $this->decodeData($_COOKIE[$full_key]);
                if ($data === null) {
                    throw new \Exception('Invalid cookie structure');
                }

                if ($data['expire'] > 0 && $current_timestamp > $data['expire']) {
                    $this->delete($key);
                    return false;
                }

                return array(
                    'value' => $data['value'],
                    'expire' => $data['expire'],
                    'set_at' => $data['set_at'],
                    'age' => $current_timestamp - $data['set_at'],
                );

            } catch (\Exception $e) {
                $this->delete($key);
                error_log("LUCookie::getMeta() failed for '$key': " . $e->getMessage());
                return false;
            }
        }

        public function delete($key) {
            $full_key = $this->prefix . $key;
            return $this->setCookieCompat(
                $full_key,
                '',
                time() - 3600,
                $this->path,
                $this->domain,
                $this->secure,
                $this->httponly,
                $this->samesite
            );
        }

        public function destroy() {
            $deleted = false;
            foreach ($_COOKIE as $key => $value) {
                if (strpos($key, $this->prefix) === 0) {
                    $original_key = substr($key, strlen($this->prefix));
                    $this->delete($original_key);
                    $deleted = true;
                }
            }
            return $deleted;
        }

        public function gc() {
            if (mt_rand(1, 100) > 20) {
                return;
            }

            $current_timestamp = time(); // ✅ 统一使用 time()

            foreach ($_COOKIE as $key => $value) {
                if (strpos($key, $this->prefix) === 0) {
                    try {
                        $data = $this->decodeData($value);
                        if ($data !== null && isset($data['expire']) && $data['expire'] > 0 && $current_timestamp > $data['expire']) {
                            $original_key = substr($key, strlen($this->prefix));
                            $this->delete($original_key);
                        }
                    } catch (\Exception $e) {
                        $original_key = substr($key, strlen($this->prefix));
                        $this->delete($original_key);
                    }
                }
            }
        }
    }
}