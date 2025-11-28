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
        private $prefix;
        private $path = '/';
        private $domain;
        private $secure;
        private $httponly = true;
        private $samesite = 'Lax';

        private function __construct( string $prefix, string $custom_domain ) {

            $this->secure = (new LUUrl())->is_https();
            $this->domain = $this->getCurrentDomain( $custom_domain );
            $this->prefix = $prefix;
        }

        private function __clone() {}

        /**
         *
         * @param string $default_encrypt_key:数据加密的key
         * @param string $prefix:cookie的前缀
         * @param string $custom_domain
         * @return self|null
         */
        public static function getInstance( string $prefix='maosiji_lu_cookie_', string $custom_domain='' ) {

            if (self::$instance === null) {
                self::$instance = new self( $prefix, $custom_domain );
            }

            return self::$instance;
        }

        /**
         * 设置 cookie
         * @param string $key
         * @param string $value
         * @param int $expire_seconds : 过期秒数，默认 300
         * @return bool
         */
        public function set( string $key, string $value, int $expire_seconds=300 ) {

            $current_timestamp = time();
            $full_key = $this->prefix . $key;
            $expire_time = $expire_seconds > 0 ? $current_timestamp + $expire_seconds : 0;

            // PHP 的原生 setcookie() 虽然也支持设置过期时间，客户端可以篡改 Cookie（比如修改过期时间）
            $cookie_data = array(
                'value'  => $value,
                'expire' => $expire_time,
                'set_at' => $current_timestamp
            );

            $encoded_value = $this->_encode_data($cookie_data);

            $set = $this->_setCookieCompat(
                $full_key,
                $encoded_value,
                $expire_time,
                $this->path,
                $this->domain,
                $this->secure,
                $this->httponly,
                $this->samesite
            );

            if ( $set ) {
                $_COOKIE[$full_key] = $encoded_value;
            }

            return $set;
        }

        /**
         * 根据 key 获取 cookie 的值
         * @param string $key
         * @return false|mixed
         */
        public function get( string $key ) {

            $full_key = $this->prefix . $key;
            if (!isset($_COOKIE[$full_key])) {
                return false;
            }

            $current_timestamp = time();

            try {
                $data = $this->_decode_data($_COOKIE[$full_key]);
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

        /**
         * 根据 key 获取 cookie 的所有信息
         * @param string $key
         * @return array|false
         */
        public function get_meta( string $key ) {

            $full_key = $this->prefix . $key;
            if (!isset($_COOKIE[$full_key])) {
                return false;
            }

            $current_timestamp = time();

            try {
                $data = $this->_decode_data($_COOKIE[$full_key]);
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

        /**
         * 检查某个 cookie 是否存在
         * @param string $key
         * @param string $value
         * @param bool $is_delete :核查后是否立即删除
         * @return bool
         */
        public function check( string $key, string $value, bool $is_delete=false ) {

            $return = false;
            $full_key = $this->prefix . $key;

            if ( hash_equals( $this->get($full_key), $value ) ) {
                $return = true;
            }
            if ( $is_delete ) {
                $this->delete($key);
            }

            return $return;
        }

        /**
         * 根据 key 删除某个 cookie
         *
         * @param string $key
         * @return bool
         */
        public function delete( string $key ) {

            $full_key = $this->prefix . $key;

            unset($_COOKIE[$full_key]);

            return $this->_setCookieCompat(
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

        /**
         * 清除本类设置的 cookie
         *
         * @return bool
         */
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

        /**
         * 获取当前域名
         * @param string $custom_domain
         * @return false|mixed|string
         */
        private function getCurrentDomain( string $custom_domain='' ) {

            if ( is_string($custom_domain) && $custom_domain!=='' ) {
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

        /**
         * 设置 cookie
         *
         * @param string $key
         * @param string $value
         * @param int $expire_time :过期时间
         * @param string $path
         * @param string $domain
         * @param string $secure
         * @param string $httponly
         * @param string $samesite
         * @return bool
         */
        private function _setCookieCompat(
            string $key,
            string $value,
            int $expire_time,
            string $path,
            string $domain,
            string $secure,
            string $httponly,
            string $samesite = 'Lax'
        ) {

            $cookie = sprintf(
                '%s=%s; expires=%s; path=%s%s%s%s%s',
                rawurlencode($key),
                rawurlencode($value),
                gmdate('D, d-M-Y H:i:s T', $expire_time > 0 ? $expire_time : 2147483647),
                $path,
                $domain ? '; domain=' . $domain : '',
                $secure ? '; secure' : '',
                $httponly ? '; HttpOnly' : '',
                $samesite ? '; SameSite=' . $samesite : ''
            );

            if ( headers_sent() ) {
                return false;
            }

            header('Set-Cookie: ' . $cookie, false);

            return true;
        }

        /**
         * 将数组转为 base64 字符串
         *
         * @param array $data
         * @return string
         */
        private function _encode_data( array $data ) {
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
         * 将 base64 字符串 还原为数组
         *
         * @param $_encode_data
         * @return array|null
         */
        private function _decode_data( string $_encode_data ) {

            $plain = base64_decode($_encode_data, true);
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







    }
}